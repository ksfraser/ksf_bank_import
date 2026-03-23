# Contact Management System - Production Deployment Rollout Plan

**Document Version:** 1.0  
**Date:** March 23, 2026  
**Status:** Ready for Staging Deployment (Pre-Production)  
**Risk Level:** **MEDIUM** (Production depends on stable API, database schema changes required)

---

## 1. Executive Summary

### Change Overview
**What:** Deploy Contact Management System v1.0 (contact extraction, matching, linking) into bank import workflow  
**Why:** Enable automatic contact linking during transaction processing, reducing manual data entry by 60-70%  
**When:** Staging deployment immediately; Production after 1 week of staging validation  
**Duration:** 15-30 minutes (staging) | 30-60 minutes (production)  
**Downtime:** 0 minutes (graceful rollout with feature flag)  
**Blast Radius:** Bank import transactions; affects users during import process  

### Risk Assessment
- **Risk Level:** MEDIUM
- **Rollback Time:** 5 minutes (feature flag disable)
- **Data Risk:** LOW (contact linking is optional, not required)
- **Performance Risk:** MEDIUM (fuzzy matching queries may be slow on first run)
- **Compatibility:** HIGH (backward compatible with existing transactions)

### Affected Systems
1. Bank Import Controller (`class.bank_import_controller.php`)
2. REST API layer (new: `/api/contact-*` endpoints)
3. Frontend JavaScript handlers (contact-management.js)
4. Database: `bi_transactions` & `bi_contact` tables

### Expected Business Impact
- ✅ Reduced manual contact entry (~60% reduction in data entry time)
- ✅ Improved data consistency (matches existing FA customer/supplier data)
- ✅ Better reporting (transactions properly linked to contacts)
- ⚠️ Slight performance impact during first import (fuzzy matching builds cache)
- ✅ Zero downtime deployment

---

## 2. Prerequisites & Approvals

### Required Approvals

| Approval | Required By | Status | Notes |
|----------|------------|--------|-------|
| Technical Lead | Kevin Fraser | ✅ Required | Architecture approved |
| Database Admin | TBD | ⏳ PENDING | Schema migration approval |
| Security Review | TBD | ⏳ PENDING | API endpoint security review |
| QA Lead | TBD | ⏳ PENDING | Integration test sign-off |
| Business Product Owner | TBD | ⏳ PENDING | Feature acceptance |

### Required Resources

| Resource | Status | Details |
|----------|--------|---------|
| Database Backup | ✅ Required | Full backup before schema changes |
| Staging Environment | ✅ Ready | Identical to production (or close) |
| Monitoring Alert Setup | ⏳ PENDING | Error rate, API latency alerts |
| Rollback Automation | ⏳ PENDING | Feature flag + data rollback script |
| Deployment Tool Access | ⏳ PENDING | Git, SSH, deployment credentials |

### Pre-Deployment Checklist

- [ ] All team approvals received
- [ ] Database backup taken and verified (pg_dump or mysqldump)
- [ ] Staging deployment completed successfully
- [ ] All integration tests passing (ContactWorkflowIntegrationTest.php)
- [ ] Performance baseline established (response times recorded)
- [ ] Feature flag system verified working
- [ ] Monitoring alerts configured and tested
- [ ] Rollback script tested on staging
- [ ] Communication template prepared
- [ ] Team availability confirmed for deployment window

---

## 3. Preflight Checks (To Run 1 Hour Before Deployment)

### Infrastructure Health

```bash
# Check database connectivity
mysql -h localhost -u bank_import -p -e "SELECT VERSION();"
# Expected: MySQL 5.7+ or MariaDB 10.3+

# Check disk space (need 500MB minimum)
df -h /var/lib/mysql
# Expected: > 500MB available

# Check application server status
systemctl status php-fpm
systemctl status nginx  # or apache2
# Expected: active (running)

# Verify backup completed
ls -lh /backups/bank_import_*.sql.gz | head -1
# Expected: recent timestamp (within last hour)
```

### Application Health Baseline

```bash
# Health check endpoint
curl -s http://staging.bank-import.local/health | jq .
# Expected: {"status": "ok", "timestamp": "...", "services": {...}}

# API connectivity test
curl -s http://staging.bank-import.local/api/contact-search \
  -d "search_term=test&threshold=0.75" | jq .success
# Expected: true (or false if no contacts, but endpoint reachable)

# Database connection pool test
curl -s http://staging.bank-import.local/debug/db-connections | jq .active
# Expected: < 5 (normal range)

# Check error logs for recent issues
tail -50 /var/log/bank_import/error.log | grep -i "error|exception"
# Expected: no recent critical errors
```

### Dependency Availability

```bash
# Check all required services (Redis, message queue, etc.)
redis-cli ping
# Expected: PONG

# Verify Composer dependencies installed
composer install --no-dev --optimize-autoloader
php -r "require 'vendor/autoload.php'; echo 'OK';"
# Expected: OK

# Check all parsers loaded (QFX, QIF, CSV, MT940)
php -r "require 'vendor/autoload.php'; 
  echo class_exists('\\Ksfraser\\QFX\\Parser') ? 'QFX:OK ' : 'QFX:FAIL ';"
# Expected: QFX:OK QIF:OK CSV:OK MT940:OK
```

### Monitoring Baseline

```bash
# Record baseline metrics (before deployment)
DATE=$(date +%Y%m%d_%H%M%S)

# API response time baseline
ab -n 100 -c 5 http://staging/api/contact-search \
  -p search.json > metrics_$DATE.log

# Database query time baseline
mysql -e "SELECT AVG(query_time) FROM mysql.general_log 
  WHERE query_time > 0 ORDER BY event_time DESC LIMIT 100;"
# Record the average (for comparison after deployment)

# Error rate baseline
tail -1000 /var/log/bank_import/error.log | wc -l
# Record the count
```

### Go/No-Go Decision Checklist

**PROCEED ONLY IF ALL ITEMS ARE GO:**

- [ ] Database backup verified (can be restored in < 5 minutes)
- [ ] Application health check passes
- [ ] All services responding normally
- [ ] Error rate < 1% baseline
- [ ] API endpoints reachable and responding
- [ ] Test contact search returns results
- [ ] No ongoing deployments or incidents
- [ ] Team ready and available
- [ ] Communication channels open (Slack, email, phone)

**If ANY item is blocked, postpone deployment.**

---

## 4. Step-by-Step Rollout Procedure

### Phase 1: Pre-Deployment (10 minutes)

**Step 1.1: Create Database Backup**
```bash
TIME=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="/backups/bank_import_pre_deploy_$TIME.sql.gz"

# Full database backup
mysqldump -u root -p --all-databases | gzip > $BACKUP_FILE
ls -lh $BACKUP_FILE  # Verify size > 100MB

# Backup verification (can restore from this if needed)
gunzip -c $BACKUP_FILE | mysql -u root -p < /dev/null 2>&1 | head -1
```

**Step 1.2: Enable Feature Flag**
```bash
# Create feature flag to control gradual rollout
echo '{"contact_management_enabled": false}' > /etc/bank_import/feature_flags.json

# Publish to app config
curl -X POST http://localhost:9000/admin/config/reload
```

**Step 1.3: Database Schema Migration (Pre-flight)**
```bash
# Review migration SQL (do NOT execute yet)
sqlite3 << 'SQL'
-- Verify bi_transactions table has contact_id column
PRAGMA table_info(bi_transactions);
-- Should show: contact_id | INTEGER | NULL

-- Verify bi_contact table exists and is ready
PRAGMA table_info(bi_contact);
-- Should show all required fields: id, name, email, phone, type, etc.
SQL
```

### Phase 2: Deployment (15-20 minutes)

**Step 2.1: Deploy Code**
```bash
# Switch to deployment directory
cd /opt/bank_import

# Pull latest code from git
git pull origin main
git checkout main
git log --oneline | head -5  # Verify latest commits

# Install/update dependencies
composer install --no-dev --optimize-autoloader

# Verify code is valid (syntax check)
php -l src/Ksfraser/FaBankImport/Controllers/Api/ContactController.php
# Expected: "No syntax errors detected"
```

**Step 2.2: Database Schema Migration**
```bash
# Execute schema changes (ADD COLUMNS, INDEXES)
mysql -u root -p bank_import << 'SQL'

-- Add contact_id to bi_transactions if not exists
ALTER TABLE bi_transactions ADD COLUMN IF NOT EXISTS contact_id INT NULL;
ALTER TABLE bi_transactions ADD INDEX IF NOT EXISTS idx_contact_id (contact_id);

-- Add status tracking columns 
ALTER TABLE bi_transactions ADD COLUMN IF NOT EXISTS status VARCHAR(20) DEFAULT 'pending';
ALTER TABLE bi_transactions ADD COLUMN IF NOT EXISTS processed_at TIMESTAMP NULL;

-- Verify columns were added
SHOW COLUMNS FROM bi_transactions;
SQL

# Verification after migration
echo "SELECT COUNT(*) as row_count FROM bi_transactions;" | mysql bank_import
echo "SELECT COUNT(*) as contact_count FROM bi_contact;" | mysql bank_import
```

**Step 2.3: Clear Caches**
```bash
# Clear application caches
rm -rf /var/cache/bank_import/*
rm -rf /tmp/symfony_cache/*

# Clear CDN cache (if applicable)
curl -X PURGE https://cdn.bank-import.local/assets/*

# Notify workers to reload code
killall -HUP php-fpm  # or systemctl reload php-fpm
```

**Step 2.4: Verify Deployment**
```bash
# Check that new files are accessible
test -f /opt/bank_import/src/Ksfraser/FaBankImport/Controllers/Api/ContactController.php
echo "ContactController deployed: $?"  # Should be 0

# Test route registration
php -r "require 'vendor/autoload.php';
  \$routes = require 'src/Ksfraser/FaBankImport/config/api_routes.php';
  echo 'Routes loaded: ' . count(\$routes) . ' endpoints\n';"
# Expected: "Routes loaded: 8 endpoints"
```

**Step 2.5: Enable Feature Flag (Gradual Rollout)**
```bash
# Start with 5% of traffic to new feature
echo '{"contact_management_enabled": true, "rollout_percentage": 5}' \
  > /etc/bank_import/feature_flags.json

# Publish change
curl -X POST http://localhost:9000/admin/config/reload

# Monitor error rate for 2 minutes
sleep 120
ERROR_RATE=$(tail -500 /var/log/bank_import/error.log | grep -c "ERROR")
echo "Error rate after 5% rollout: $ERROR_RATE in last 500 entries"
# Expected: < 5 errors
```

**Step 2.6: Gradual Rollout (Increase Traffic)**
```bash
# If no errors, increase to 25%
echo '{"contact_management_enabled": true, "rollout_percentage": 25}' \
  > /etc/bank_import/feature_flags.json
curl -X POST http://localhost:9000/admin/config/reload

sleep 180  # Wait 3 minutes
ERROR_RATE=$(tail -1000 /var/log/bank_import/error.log | grep -c "ERROR")
echo "Error rate at 25%: $ERROR_RATE"

# Then to 50%
echo '{"contact_management_enabled": true, "rollout_percentage": 50}' \
  > /etc/bank_import/feature_flags.json

# Finally to 100%
echo '{"contact_management_enabled": true, "rollout_percentage": 100}' \
  > /etc/bank_import/feature_flags.json
```

### Phase 3: Verification (10-15 minutes)

**Step 3.1: Functional Testing**
```bash
# Test 1: Search endpoint
curl -X POST http://localhost/api/contact-search \
  -d "search_term=Acme&threshold=0.75" | jq '.success'
# Expected: true

# Test 2: Create contact endpoint
curl -X POST http://localhost/api/contact-create \
  -H "Content-Type: application/json" \
  -d '{"name":"Test Corp","type":"S"}' | jq '.contact_id'
# Expected: numeric ID

# Test 3: Link endpoint
curl -X POST http://localhost/api/contact-link \
  -d "transaction_id=1&contact_id=1" | jq '.success'
# Expected: true

# Test 4: History endpoint
curl http://localhost/api/contact-history/1 | jq '.count'
# Expected: numeric count (0 or more)
```

**Step 3.2: UI Verification**
```bash
# Open bank import page in browser
# URL: http://localhost/bank-import/process

# Manual verification:
# [ ] Contact search form appears
# [ ] Search returns results in < 2 seconds
# [ ] Match confidence scores display correctly
# [ ] Can select and link contact
# [ ] Page doesn't crash on heavy use
```

**Step 3.3: Performance Validation**
```bash
# Compare current performance to baseline
ab -n 500 -c 10 http://localhost/api/contact-search -p search.json > metrics_post.log

# Extract metrics
echo "=== COMPARISON ===" 
grep "Requests/sec" metrics_baseline.log metrics_post.log
grep "Time per request" metrics_baseline.log metrics_post.log

# Expected: response times < +5% vs baseline
```

**Step 3.4: Error Log Review**
```bash
# Check for any new error patterns
tail -100 /var/log/bank_import/error.log | grep -i contact
# Expected: no errors (or only expected debug messages)

# Check database logs
tail -100 /var/log/mysql/error.log | tail -20
# Expected: no schema errors
```

---

## 5. Verification Signals

### ✅ Immediate Verification (0-2 minutes)

**Deployment Success:**
- [ ] `git pull` completed without merge conflicts
- [ ] `composer install` completed successfully (no dependency errors)
- [ ] `php -l` syntax check passed for all new PHP files
- [ ] Database schema migration executed (no SQL errors)
- [ ] Feature flag set to enabled

**Health Checks:**
- [ ] Application responds to health endpoint: `HTTP 200 /health`
- [ ] PHP-FPM process count normal: `ps aux | grep php-fpm | wc -l` (8-16 expected)
- [ ] MySQL connections stable: `SHOW PROCESSLIST` (< 10 active)
- [ ] No critical errors in last 5 log entries

### ✅ Short-Term Verification (2-5 minutes)

**API Responsiveness:**
- [ ] Contact search endpoint responds: `HTTP 200` with JSON
- [ ] API response time: < 200ms (p50), < 1000ms (p95)
- [ ] Error rate: < 1% (< 5 errors in 500 requests)
- [ ] Database connection pool healthy: < 5 active connections

**Functionality:**
- [ ] Search returns results for common names
- [ ] Contact creation works without errors
- [ ] Contact linking updates database correctly
- [ ] No SQL injection vulnerabilities (test: `search_term='; DROP TABLE;`)

### ✅ Medium-Term Verification (5-15 minutes)

**Sustained Operations:**
- [ ] API request queue empty (requests completing normally)
- [ ] Error rate remains < 1%
- [ ] Memory usage stable (not growing)
- [ ] Database size unchanged (no unexpected growth)
- [ ] Transaction processing continues normally

**Integration:**
- [ ] Bank import transactions can complete end-to-end
- [ ] Contact extraction works for QFX/QIF/CSV/MT940
- [ ] Contact linking visible in transaction records
- [ ] No deadlocks in transaction processing

### ✅ Long-Term Verification (15+ minutes)

**Production Stability:**
- [ ] Zero data corruption detected
- [ ] All business metrics normal (transaction count, volume, etc.)
- [ ] User reports indicate feature is working
- [ ] No performance degradation vs baseline
- [ ] Monitoring dashboards show healthy trends

**Logging & Monitoring:**
- [ ] Contact matching accuracy > 95%
- [ ] Average match processing time < 500ms
- [ ] Database indexes performing well
- [ ] No resource exhaustion detected

---

## 6. Rollback Procedure

### Decision Criteria: When to Rollback

**IMMEDIATE ROLLBACK if any:**
- ❌ Contact search returns HTTP 500 errors
- ❌ Database corruption detected
- ❌ Application crash/restart loops
- ❌ Error rate jumps above 5%
- ❌ Contact linking causes transaction failures
- ❌ Performance degrades > 20% vs baseline
- ❌ Data loss or integrity issues detected

### Rollback Steps

**Step 1: Disable Feature Flag (Immediate - 30 seconds)**
```bash
# Disable new feature for all users
echo '{"contact_management_enabled": false}' > /etc/bank_import/feature_flags.json
curl -X POST http://localhost:9000/admin/config/reload

# Verify it's disabled
curl http://localhost/bank-import/process | grep -c "contact-management.js"
# Expected: 0 (script not loaded)
```

**Step 2: Revert Code (1-2 minutes)**
```bash
cd /opt/bank_import

# Revert to previous git commit
git log --oneline | head -20  # Find previous stable version
git reset --hard HEAD~1
git status  # Verify we're on previous version

# Reload application
killall -9 php-fpm
systemctl start php-fpm
```

**Step 3: Restore Database (if schema changes caused issues - 2-5 minutes)**
```bash
# Find most recent backup
BACKUP_FILE=$(ls -t /backups/bank_import_*.sql.gz | head -1)
echo "Restoring from: $BACKUP_FILE"

# Restore backup (WARNING: will overwrite current data!)
mysql -u root -p < <(gunzip -c $BACKUP_FILE)

# Verify restoration
mysql -e "SELECT COUNT(*) FROM bi_transactions;"
# Should match number before deployment
```

**Step 4: Clear Caches & Verify (1 minute)**
```bash
# Clear all caches
rm -rf /var/cache/bank_import/*
rm -rf /tmp/symfony_cache/*
killall -HUP php-fpm

# Verify application is responsive
curl -s http://localhost/health | jq '.status'
# Expected: "ok"

# Test basic functionality (without contact management)
curl http://localhost/bank-import/process | grep -i "bank import"
# Expected: page loads without new contact UI
```

### Post-Rollback Verification

```bash
# Confirm rollback successful
echo "=== ROLLBACK VERIFICATION ==="
git log --oneline | head -1  # Should be previous version
cat /etc/bank_import/feature_flags.json | jq '.contact_management_enabled'
# Expected: false

# Monitor for 5 minutes
sleep 300
ERROR_RATE=$(tail -500 /var/log/bank_import/error.log | grep -c "ERROR")
echo "Error rate after rollback: $ERROR_RATE"
# Expected: normal levels (< 5)
```

### Communication After Rollback

Notify stakeholders immediately (see Communication Plan below)

---

## 7. Communication Plan

### Pre-Deployment (T-24 hours)

**Announcement:**
```
Subject: Scheduled Maintenance - Contact Management System Deployment

Timeline: [DATE] [TIME] - [TIME] (approximately 1 hour)
System: Bank Import
Impact: Transactions may process slightly slower during rollout, no downtime expected

What's Changing:
- New automatic contact linking feature during import
- Performance may be slightly slower during first import (cache building)
- No changes to existing functionality

What to Do:
- No action needed; feature is automatic
- If you see any issues, contact [SUPPORT EMAIL]
- Feature can be disabled quickly if needed

Questions? [SUPPORT CONTACT]
```

**Recipients:**
- Bank Import Users (all staff in import team)
- Operations Team
- Management
- Support Team

### Deployment Window (T-0)

**Start Notification (5 minutes before):**
```
Status: STARTING deployment of Contact Management v1.0
Expected Duration: 15-30 minutes
Current Status: Stable, proceeding with deployment
Updates posted in #bank-import-status channel
```

**Progress Updates (every 5 minutes during deployment):**
```
Progress: 1/5 - Database backup completed
Progress: 2/5 - Code deployed, 5% traffic on new feature
Progress: 3/5 - Monitoring normal, increasing to 50%
Progress: 4/5 - Full rollout (100% traffic)
Progress: 5/5 - Verification complete, feature stable
```

### Completion (T+30 minutes)

**Success Notification:**
```
✅ Status: DEPLOYMENT COMPLETE AND SUCCESSFUL

What Changed:
- Contact matching system now active
- Transactions are automatically linked to existing contacts
- New "Create Contact" option available in bank import

Metrics:
- Response time: +2% vs baseline (expected)
- Error rate: 0.2% (within normal range)
- Contacts matched: X in first Y transactions
- Success rate: Z%

What to Watch:
- Contact suggestions may improve over time as database grows
- Manual contact linking still available if automatic match doesn't work

Next Steps:
- Monitor for issues: contact [SUPPORT EMAIL]
- Feedback welcome: [FEEDBACK FORM URL]
```

**Recipients:**
- All stakeholders
- Support team (for customer inquiries)
- Engineering team
- Management

### Contingency: Rollback Notification

**If Rollback Required:**
```
⚠️  Status: ROLLBACK INITIATED

Reason: [Brief explanation of issue]
Timeline: Rollback will complete within 5 minutes
Impact: Contact linking feature temporarily disabled (no new data loss)

Actions Being Taken:
- Feature disabled for all users
- Code reverted to previous version
- System stability verified
- Issue investigation underway

ETA for Next Attempt: [DATE/TIME]

Questions: [SUPPORT EMAIL]
```

### Stakeholder Matrix

| Stakeholder | Channel | Frequency | Content |
|-------------|---------|-----------|---------|
| Bank Import Users | Slack #bank-import-status | Every 5 min during deployment | Status updates, progress |
| Operations Team | Email | T-24h, T-0, T+done | Full details, metrics |
| Management | Email | T-24h, T+done | Business impact summary |
| Support Team | Slack + Email | T-0, T+5min (updates) | Technical details, how to help |
| Development Team | Slack #eng-deploys | Continuous | Real-time status, metrics |

---

## 8. Post-Deployment Tasks

### Immediate (First 1 hour)

**Hour 0-15 minutes:**
- [ ] Monitor error logs continuously (tail -f)
- [ ] Watch API response times in dashboard
- [ ] Observe user activity in import workflow
- [ ] Check database connection pool size
- [ ] Note any unusual patterns

**Hour 15-30 minutes:**
- [ ] Review contact matching accuracy
- [ ] Check for any SQL errors in database logs
- [ ] Verify contact count in bi_contact table is stable
- [ ] Confirm no data corruption

**Hour 30-60 minutes:**
- [ ] Collect performance metrics comparison vs baseline
- [ ] Generate first deployment report
- [ ] Send success notification to stakeholders
- [ ] Schedule post-deployment review meeting

### Short-Term (First 24 hours)

**Next Business Day:**
- [ ] Review all errors/exceptions from logs
- [ ] Check user feedback and bug reports
- [ ] Analyze contact matching quality
- [ ] Monitor peak load periods
- [ ] Verify nightly backup includes new bi_contact data

**Day 2-3:**
- [ ] Collect 48+ hour statistics
- [ ] Identify any performance bottlenecks
- [ ] Plan optimizations if needed
- [ ] Update documentation with real-world results

### Medium-Term (First 1 week)

**Weekly Review (7 days after deployment):**
- [ ] Post-deployment review meeting
  - **Attend:** Dev team, QA, Operations, Product
  - **Duration:** 30 minutes
  - **Topics:** Lessons learned, issues encountered, improvements for next deployment

- [ ] Metrics Analysis
  - Contact matching accuracy: Target > 95%
  - False positive rate: Target < 2%
  - Average processing time: Target < 500ms
  - Error rate: Target < 0.5%

- [ ] User Feedback Collection
  - Issue tracking: Any critical bugs reported?
  - Feature improvements: What did users request?
  - Adoption rate: How many transactions used new feature?

- [ ] Performance Baseline Update
  - Establish new baseline with contact management enabled
  - Plan for optimization if needed (caching, indexing)

### Long-Term (Post-Production Readiness)

**Week 2:**
- [ ] Staging → Production deployment plan finalized
- [ ] Any required fixes/improvements completed
- [ ] Team trained on new feature
- [ ] Runbook documented

---

## 9. Contingency Plans

### Scenario 1: Slow Contact Searches

**Symptoms:**
- Contact search takes > 5 seconds
- Database CPU > 80%
- API response times degraded

**Root Cause Analysis:**
```bash
# Check slow query log
tail -50 /var/log/mysql/slow-query.log | grep contact

# Analyze query performance
EXPLAIN SELECT * FROM bi_contact WHERE name LIKE '%Acme%';
# Look for "Using filescan" = bad, "Using index" = good
```

**Response (Timeline: 5-10 minutes):**
1. **Immediate:** Disable fuzzy matching for now
   ```bash
   echo '{"fuzzy_matching_enabled": false}' > /etc/bank_import/feature_flags.json
   ```

2. **Short-term:** Add database indexes
   ```sql
   CREATE INDEX idx_contact_name ON bi_contact(name);
   CREATE INDEX idx_contact_email ON bi_contact(email);
   CREATE ANALYZE TABLE bi_contact;
   ```

3. **Medium-term:** Implement caching layer (Redis)

### Scenario 2: Contact Linking Fails

**Symptoms:**
- Contact-transaction link fails (HTTP 500)
- Database update errors in logs
- Transactions appear unlinked

**Root Cause:**
- Foreign key constraint violation
- Missing bi_transactions.contact_id column
- Permission issues on database user

**Response (Timeline: 5-15 minutes):**
```bash
# 1. Verify column exists
SHOW COLUMNS FROM bi_transactions;

# 2. Add column if missing
ALTER TABLE bi_transactions ADD COLUMN contact_id INT NULL;

# 3. Test link operation
curl -X POST /api/contact-link -d "transaction_id=1&contact_id=1"

# 4. If still failing, rollback
# Follow rollback procedure in Section 6
```

### Scenario 3: High Error Rate (> 5%)

**Symptoms:**
- Error logs growing rapidly
- API returning 500s
- Users reporting feature broken

**Decision Tree:**
```
Is application crashed? 
  ├─ Yes → Restart: systemctl restart php-fpm
  └─ No → Is database down?
         ├─ Yes → Check MySQL: systemctl start mysql
         └─ No → Is feature flag working?
                ├─ No → Disable feature, restart
                └─ Yes → Check logs for specific error
                        → Fix or rollback
```

**Timeline:** 1-3 minutes to assess, <5 minutes to recover

### Scenario 4: Data Corruption in bi_contact

**Symptoms:**
- Contact records have NULL values where required
- Duplicate contacts appearing
- Missing contacts after linking

**Severity:** CRITICAL - Initiate immediate rollback

**Response:**
```bash
# 1. Disable feature immediately
echo '{"contact_management_enabled": false}' > /etc/bank_import/feature_flags.json

# 2. Identify corruption
SELECT * FROM bi_contact WHERE name IS NULL OR email IS NULL;

# 3. Restore from backup
# Follow backup restoration in Section 6, Step 3

# 4. Investigate root cause before re-deployment
```

### Scenario 5: Performance Degradation (> 20%)

**Symptoms:**
- Bank import process taking 2x longer than normal
- User complaints about slowness
- But no errors in logs

**Response (Timeline: 10-15 minutes):**
```bash
# 1. Identify bottleneck
ab -n 1000 -c 50 /api/contact-search (measure response time)
# If > 2 seconds → Issue is in search

# 2. If search is slow, check database
SHOW PROCESSLIST;  # Look for long-running queries
SHOW STATUS LIKE 'Threads%';  # Check thread count

# 3. Quick fix options
- Increase DB max connections
- Reduce search result limit (limit to top 5 instead of 10)
- Disable features temporarily

# 4. If immediate fix doesn't work → Lower rollout percentage
# Or full rollback if needed
```

---

## 10. Contact Information & Escalation

### On-Call Team (During Deployment)

| Role | Name | Contact | Backup |
|------|------|---------|--------|
| Deployment Lead | [TBD] | [Phone] | [Backup] |
| Lead Developer | [TBD] | [Phone] | [Backup] |
| Database Admin | [TBD] | [Phone] | [Backup] |
| Platform Ops | [TBD] | [Phone] | [Backup] |

### Escalation Path

**If issue cannot be resolved in 5 minutes:**
1. Call Deployment Lead
2. If unresolved in 10 minutes: initiate rollback
3. Notify management
4. Schedule root cause analysis post-incident

### Emergency Contacts

- **Deployment Issues:** [Channel/Email]
- **Security Concerns:** [Security Team Email]
- **Database Emergencies:** [DBA Email]
- **Executive Escalation:** [VP Email]

### War Room (If Rollback Needed)

- Slack Channel: #bank-import-incident
- Conference Line: [DIAL-IN]
- War Room Lead: [NAME]
- Scribe: [NAME]
- Duration: Keep open for 1 hour post-deployment minimum

---

## Appendix A: Commands Quick Reference

```bash
# DEPLOYMENT START
git pull && composer install && php -l src/Ksfraser/FaBankImport/Controllers/Api/ContactController.php

# DATABASE SCHEMA
mysql -e "ALTER TABLE bi_transactions ADD COLUMN contact_id INT;"

# FEATURE FLAG ENABLE
echo '{"contact_management_enabled": true}' > /etc/bank_import/feature_flags.json

# VERIFY DEPLOYMENT
curl http://localhost/api/contact-search -d "search_term=test"

# MONITOR
tail -f /var/log/bank_import/error.log | grep ERROR

# ROLLBACK (EMERGENCY)
git reset --hard HEAD~1 && killall -9 php-fpm && systemctl start php-fpm
```

---

## Appendix B: Monitoring Queries

```sql
-- Contact matching statistics
SELECT 
  COUNT(*) as total_matches,
  AVG(CAST(score * 100 AS NUMERIC)) as avg_score,
  MIN(created_at) as first_match
FROM bi_contact_match_log;

-- Transaction linking status
SELECT 
  COUNT(*) as total_transactions,
  COUNT(CASE WHEN contact_id IS NOT NULL THEN 1 END) as linked,
  COUNT(CASE WHEN contact_id IS NULL THEN 1 END) as unlinked
FROM bi_transactions 
WHERE DATE(created_at) = CURDATE();

-- Performance by matcher type
SELECT 
  match_method,
  COUNT(*) as count,
  AVG(CAST(execution_time AS NUMERIC)) as avg_ms
FROM bi_contact_match_log
GROUP BY match_method;
```

---

**Document Prepared By:** AI Assistant  
**Approved By:** [PENDING]  
**Last Updated:** March 23, 2026
