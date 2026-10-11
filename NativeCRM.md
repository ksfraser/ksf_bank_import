---

## Assistant (Build · Big Pickle · 7.0s)

=== leads table ===
CREATE TABLE IF NOT EXISTS `0_fa_crm_leads` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `debtor_no` VARCHAR(20) NOT NULL,
    `lead_source` VARCHAR(50) DEFAULT NULL,
    `lead_status` VARCHAR(30) DEFAULT 'new',
    `rating` VARCHAR(30) DEFAULT NULL,
    `annual_revenue` DECIMAL(15,2) DEFAULT NULL,
    `employee_count` INT(11) DEFAULT NULL,
    `industry` VARCHAR(50) DEFAULT NULL,
    `website` VARCHAR(255) DEFAULT NULL,
    `phone` VARCHAR(20) DEFAULT NULL,
    `email` VARCHAR(100) DEFAULT NULL,
    `address` TEXT,
    `assigned_to` VARCHAR(100) DEFAULT NULL,
    `campaign_id` INT(11) DEFAULT NULL,
    `converted_date` DATETIME DEFAULT NULL,
    `converted_to_debtor_no` VARCHAR(20) DEFAULT NULL,
    `notes` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_debtor_no` (`debtor_no`),
    KEY `idx_lead_status` (`lead_status`),
    KEY `idx_assigned_to` (`assigned_to`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- CRM Contact Accounts (cross-account contact assignments)
CREATE TABLE IF NOT EXISTS `0_fa_crm_contact_accounts` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `contact_id` INT(11) NOT NULL,
    `debtor_no` VARCHAR(20) NOT NULL,
    `is_primary` TINYINT(1) DEFAULT 0,
    `role` VARCHAR(50) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_contact_debtor` (`contact_id`, `debtor_no`),
    KEY `idx_debtor_no` (`debtor_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- CRM Realms (opportunity realms/categories)

=== NATIVE 0_crm_contacts ===
Field	Type	Null	Key	Default	Extra
id	int(11)	NO	PRI	NULL	auto_increment
person_id	int(11)	NO		0	
type	varchar(20)	NO	MUL	NULL	
action	varchar(20)	NO		NULL	
entity_id	varchar(11)	YES		NULL	

=== native contact_accounts (branch xref) ===
Tables_in_ksf_fa (%contact_account%)
0_fa_crm_contact_accounts


Now I understand the native FA contact model:
- `0_crm_contacts` is a **person/contact profile table** with `person_id` (FK to native `0_persons`? let me check), `type` (general/sales/customer), `action`, `entity_id` (the branch/debtor reference).
- So native FA contacts = person profiles attached to a business entity (branch) via `entity_id`.

=== native person / branch tables ===
0_crm_persons
0_fa_crm_person_account_roles
0_cust_branch

=== 0_persons? ===
(no 0_persons)

=== 0_debtor_branch ===
(no 0_debtor_branch)

=== core refs to crm_contacts / crm_persons / cust_branch ===
./admin/crm_categories.php
./gl/includes/db/gl_db_accounts.inc
./gl/includes/db/gl_db_bank_accounts.inc
./gl/includes/db/gl_db_banking.inc
./gl/includes/db/gl_db_trans.inc
./gl/includes/db/gl_journal.inc
./gl/manage/gl_accounts.php
./includes/db/crm_contacts_db.inc
./includes/ui/contacts_view.inc
./includes/ui/ui_lists.inc

=== contacts db save (native) ===
custalloc_db.inc
customers_db.inc
cust_trans_db.inc
cust_trans_details_db.inc

=== how customer_branches.php stores contacts ===
105:			add_crm_contact('cust_branch', 'general', $selected_id, db_insert_id());
181:			$myrow = get_cust_branch($_POST['customer_id'], $_POST['branch_code']);
301:$table =& new_db_pager('branch_tbl', $sql, $cols, 'cust_branch');
302:$table->set_inactive_ctrl('cust_branch', 'branch_code');
321:			$contacts = new contacts('contacts', $selected_id, 'cust_branch');

```

---

## Assistant (Build · Big Pickle · 4.0s)

_Thinking:_

This is the native FA contact model. Let me read the core contact DB and view code to understand it precisely.

Key findings:
- `customer_branches.php:105`: `add_crm_contact('cust_branch', 'general', $selected_id, db_insert_id());` — when creating a branch, it adds a CRM contact of type 'general' for the `cust_branch` entity.
- `customer_branches.php:321`: `$contacts = new contacts('contacts', $selected_id, 'cust_branch');` — the contacts UI for a branch.

So native FA stores:
- `0_cust_branch` — customer branches
- `0_crm_persons` — person profiles
- `0_crm_contacts` — links person → entity (type + entity_id) with an action


**Native FA contact model (the system of record):**
- `TB_PREF . "crm_persons"` — the PERSON profile (id, ref, name, name2, address, phone, phone2, fax, email, lang, notes). This is a person (individual), NOT an account.
- `TB_PREF . "crm_categories"` — the contact "category"/role catalog (type, action, name, description, system, inactive). type ∈ {general, sales, customer}, action ∈ {general, ...} (e.g. contact, bill, ship, sales).
- `TB_PREF . "crm_contacts"` — the XREF that links a person to an entity via a category:
  - `person_id` → crm_persons.id
  - `type` + `action` → crm_categories (defines the contact role/context, e.g. type='cust_branch', action='general' or action='contact')
  - `entity_id` → the entity the contact is attached to (for a customer branch contact, entity_id = the branch id, type='cust_branch')

So the native model is exactly what the user wants in Cluster D:
- **A person** = `crm_persons` (shared identity: name, phone, email, address).
- **A person's link to an entity** (customer branch, etc.) = `crm_contacts` (the xref, with a category/role).
- One person can be linked to MULTIPLE entities/roles (via multiple crm_contacts rows).

The native functions:
- `add_crm_person(ref, name, name2, address, phone, phone2, fax, email, lang, notes, cat_ids, entity)` — create a person + their contact links.
- `update_crm_person(id, ...)` — update person + links.
- `get_crm_persons(type, action, entity, person, unique)` — query people by category/entity/person. This is the native "get contacts for entity X" query.
- `get_crm_person(id)` — one person with their contacts.
- `get_person_contacts(id)` — a person's contact links.
- `add_crm_contact(type, action, entity_id, person_id)` — add one person↔entity link.
- `delete_crm_contact(id)`, `delete_crm_contacts(...)`, `delete_entity_contacts(class, entity)`.
- `update_person_contacts(id, cat_ids, entity_id, type)`.

The native customer contact display: `customer_branches.php:321`: `$contacts = new contacts('contacts', $selected_id, 'cust_branch');` — a `contacts` UI class (includes/ui/contacts_view.inc) that lists/edits contacts for a branch.

So for the CRM module to be the "system of record" per #14/#15, the CRM's Contacts tab should:
- Read/write native `crm_persons` + `crm_contacts` (via add_crm_person/get_crm_persons, etc.), NOT the flat `0_fa_crm_contacts` table.
- The CRM's `0_fa_crm_contacts` (debtor_no-keyed, first_name/last_name) is the WRONG standalone storage — it's the bug.

=== NATIVE 0_crm_persons ===
Field	Type	Null	Key	Default	Extra
id	int(11)	NO	PRI	NULL	auto_increment
ref	varchar(30)	NO	MUL	NULL	
name	varchar(60)	NO		NULL	
name2	varchar(60)	YES		NULL	
address	tinytext	YES		NULL	
phone	varchar(30)	YES		NULL	
phone2	varchar(30)	YES		NULL	
fax	varchar(30)	YES		NULL	
email	varchar(100)	YES		NULL	
lang	char(5)	YES		NULL	
notes	tinytext	NO		NULL	
inactive	tinyint(1)	NO		0	

=== NATIVE 0_crm_categories (type/action catalog) ===
type	action	name	system
cust_branch	delivery	Deliveries	1
cust_branch	general	General	1
cust_branch	invoice	Invoices	1
cust_branch	order	Orders	1
customer	delivery	Deliveries	1
customer	general	General	1
customer	invoice	Invoices	1
customer	order	Orders	1
supplier	delivery	Deliveries	1
supplier	general	General	1
supplier	invoice	Invoices	1
supplier	order	Orders	1
user	general	System User	1


---
- `0_crm_persons` (id, ref, name, name2, address, phone, phone2, fax, email, lang, notes, inactive) — a PERSON (individual) profile. No I/F/B field.
- `0_crm_categories` (type, action, name, description, system, inactive) — the contact role/context catalog. type ∈ {cust_branch, customer, supplier, user}; action ∈ {general, delivery, invoice, order}.
- `0_crm_contacts` (id, person_id, type, action, entity_id) — the XREF linking a person to an entity via a category role. entity_id = the branch id (for cust_branch) or debtor_no (for customer/supplier) or user id.

**Key realizations for Cluster D:**
1. The native `crm_persons` + `crm_contacts` model IS the shared person/contact layer the user wants — a person (individual) with xref links to multiple entities (customer/supplier/user branches) via roles. This serves CRM, HRM, Users exactly as the user described.
2. The module's `0_fa_crm_contacts` (debtor_no-keyed, first_name/last_name flat) is the WRONG standalone storage = bug #14/#15. The CRM must read/write native.
3. The module's FK comments `person_id → crm_persons.id` in `person_account_roles`, `contact_relationships`, `life_events` were MEANT to point at native `crm_persons.id` (which exists as `0_crm_persons`) — not a missing module table. So the design intent aligns with native.
4. The native categories already cover `user` (System User), `supplier`, `customer`/`cust_branch` — so the "point at the right Debtor/Employee/Supplier/User record" maps to type ∈ {customer, cust_branch, supplier, user} (+ employee via HRM). I/F/B is NOT native — needs a CRM extension (person-level classification for approach + CASL).
5. CASL (Canada anti-spam) consent tracking is NOT in native crm_persons — needs extension fields (consent status, consent date, consent source, opt-out). The module's `0_fa_crm_customers` already has `marketing_opt_out`.

**The native UI for contacts:** `customer_branches.php` uses `new contacts('contacts', $selected_id, 'cust_branch')` from `includes/ui/contacts_view.inc`. This is the reusable UI SRP. The user's Cluster D wants CRM to reuse the shared contacts UI for CRM/HRM/Users — and native already has `contacts_view.inc` (a shared class) + the `crm_categories` to scope by type/action/entity. This is the "trait/SRP to pass display" the user asked about in #25 (Email Accounts "would I think this would be a trait").

So the design is clear and well-grounded. This is genuinely the right Cluster D design:

**Cluster D: Adopt native crm_persons + crm_contacts as the shared person/contact layer.**
- The CRM Contacts tab reads/writes native `crm_persons` (person) + `crm_contacts` (person↔entity xref), using native functions `add_crm_person`, `get_crm_persons`, `get_crm_person`, `add_crm_contact`, etc. — never the flat `0_fa_crm_contacts`.
- The native `contacts` UI class (`includes/ui/contacts_view.inc`) is the shared display/entry SRP reused by CRM, HRM, Users — satisfying the "shared contacts UI" goal.
- Add a CRM-side person extension for I/F/B (individual/family/business) + CASL consent fields (the native crm_persons has no I/F/B or consent). Either ALTER crm_persons (core) or a side table `0_fa_crm_person_profiles` (person_id, person_type I/F/B, consent_status, consent_date, consent_source, opt_out, ...). Given "don't modify core FA tables" is safer, a side table is cleaner — but the user may want the field in crm_persons. A side table `0_fa_crm_person_profiles` keyed by native `crm_persons.id` is the safe extension (no core ALTER, survives FA upgrades).
- Lead = a debtor (`debtors_master` row) with an attached contact (a crm_person linked via crm_contacts type='customer'/'cust_branch' action='general'). Lead metadata (status, source, rating) stays in `0_fa_crm_leads` keyed by debtor_no. On conversion, the debtor is activated as a real customer; the contact links persist. I/F/B on the attached person governs approach + CASL.


**Output:**
BR-CRM-001.md
BR-CRM-01-pipeline-funnel-forecast.md
BR-CRM-02-email-integration-imap-smtp.md
BR-CRM-03-quote-to-order-conversion.md
BR-CRM-04-marketing-campaigns.md
BR-CRM-05-helpdesk-tickets.md
BR-CRM-06-contracts-renewal-lifecycle.md
BR-CRM-07-crm-dashboards-kpis.md
BR-CRM-08-activity-automation-chaining.md
FR-CRM-001-Reports.md
FR-CRM-002-001-quote-native-order-wrap.md
FR-CRM-007-001-contact-classification-responder.md
UC-CRM-007-001-contact-classification.md

---

=== all refs to fa_crm_contacts (flat) across module ===
src/Ksfraser/FA/CRM/Repository/ContactsRepository.php:8: * Repository for CRM contacts (0_fa_crm_contacts).
src/Ksfraser/FA/CRM/Repository/ContactsRepository.php:25:    private string $table = 'fa_crm_contacts';
src/Ksfraser/FA/CRM/Service/ContactsService.php:74:            'table'        => '0_fa_crm_contacts',
src/Ksfraser/FA/CRM/Controller/ContactsTabController.php:13: * Manages contact persons (0_fa_crm_contacts) attached to customers.
includes/EmailImportService.php:134:        $sql = "SELECT c.id, c.debtor_no FROM " . TB_PREF . "fa_crm_contacts c
includes/EmailImportService.php:142:        $sql = "SELECT c.id, c.debtor_no FROM " . TB_PREF . "fa_crm_contacts c

---

I now have the native field mapping: `name` = First Name, `name2` = Last Name, `ref` = Reference (required!), plus address/phone/phone2/fax/email/lang/notes, and `assgn` = the categories/assignments (required at least one).

Key detail: native `add_crm_person` requires `ref` (reference) and the `assgn` categories. So the CRM Contacts tab needs to supply a ref. Also the multi-branch linkage: `assgn` allows multiple categories → one person linked to multiple branches. This confirms the design.

Now, an important architectural consideration. The native `contacts` class is coupled to native page flow (POST names like `contactsEdit[123]`, `$this->name`, simple_crud). It is NOT directly reusable inside our AbstractTabController flow, because our controller uses its own form/POST conventions. The FRD said "reuse native contacts UI SRP" — but practically, in our tab controller the reusable part is the **data functions** (`add_crm_person`, `update_crm_person`, `get_crm_persons`, `add_crm_contact`, `delete_crm_contacts`, etc.) via a new `PersonsRepository`, not the native `contacts` UI class (which is hard-wired to native POST/IDs and would fight our controller).

So the right implementation:
- New `PersonsRepository` (module-side, SRP) that wraps the native data functions (`add_crm_person`, `update_crm_person`, `get_crm_person`, `get_crm_persons`, `get_person_contacts`, `add_crm_contact`, `delete_crm_contacts`, `delete_crm_person`) and adds the I/F/B+consent profile side-table. This is the "client of native" — good.
- Repoint `ContactsService`/`ContactsRepository` (or replace them) to use `PersonsRepository`.

But wait — there's a real decision here about how much to change. The FRD plan (from the summary) was:
(a) side-table DDL in install.sql
(b) Contacts tab rewrite onto native crm_persons/crm_contacts + branch selector (#14/#15)
(c) I/F/B + CASL fields
(d) Lead = debtor + contacts + conversion (#30) + tests + migration script

Let me be decisive: implement the full native persons layer with defaults:
- person_type default 'I', manually selectable (selector) — this is the conservative default matching "Type selector individual/family/business (I/F/B) indicates approach + CASL" from the product directive. Manual selector was the actual product directive wording.
- CASL: person-level only (recommended).
- HRM: defer employee linking (out of scope) but the schema supports it later (crm_categories type could get 'employee'). Keep it simple.
- Migration: since data is throwaway, still write it idempotently (good practice, and CI-safe), but no backup ceremony needed.

Now, the critical constraint from the environment: **I must use native db_* calls**, and the native functions (`add_crm_person` etc.) are procedural FA functions available at runtime. The module code runs inside FA, so calling `add_crm_person()` directly is fine and is the "single mandatory implementation" pattern... wait, but the module uses a repository with `FaRepositoryTrait` (db_* wrappers). For tests, `tests/stubs.php` provides db_* stubs. If I call native `add_crm_person()`, unit tests would need to stub those functions. That's doable but let me check how the native functions are implemented — if they're thin wrappers over db_query, I can call them at runtime and stub them in tests.

Let me read crm_contacts_db.inc to see the exact signatures and whether they're safe to call.

Also I need to check: does the module's runtime include the native crm_contacts_db.inc? The module's index.php runs inside FA, so yes, I can `include_once($path_to_root . '/includes/db/crm_contacts_db.inc')`.

Let me read the native DB file. This is essential before writing the repository.

Let me also reconsider scope/effort. This is a substantial multi-file change. Given the conversation, I should keep momentum: read native db file, then implement (a)-(c) with tests, deploy, and leave (d) Lead for next (it's a bigger separate cluster). Actually the summary said next move was implement in slices (a),(b),(c),(d). The user said "feel free to migrate" → that's about the migration/safety gate, green-lighting the whole Cluster D implementation. I'll do it in slices and deliver.

I have the native field mapping now (`name`=First, `name2`=Last, `ref` required, `assgn`=categories, multi-branch via `assgn`). The native `contacts` UI class is hard-wired to native POST/ID conventions, so the reusable part for our controller is the **native data functions** — I'll wrap those in a module `PersonsRepository` (the "client of native" per the FRD). Let me read their exact signatures before writing anything:

---

