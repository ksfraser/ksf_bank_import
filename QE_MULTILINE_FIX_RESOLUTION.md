# Quick Entry Multi-Line Duplication Bug - RESOLVED

**Status**: ✅ FIXED  
**Commit**: 99067a9 - fix(qe_to_cart): move foreach loop outside while to fix line duplication  
**Branch**: chore/process-statements-logic-parity  
**Date Fixed**: 2026-03-27

## Problem Statement

Quick Entry (QE) multi-line bank payments were duplicating line items and showing incorrect totals:

**Example with 2-line 50/50 template (100 total):**
- Expected: Line1 = 50, Line2 = 50 (100% total)
- Actual: Line1 = 100 (processed twice), Line2 = 50 (150% total)
- Cascade pattern when reduced-base applied: 50% → 25% → 12.5%

## Root Cause

**Nested Loop Architecture Bug** in qe_to_cart() function (includes/includes.inc lines 371-589):

Each template line was processed N times (where N = number of template lines in database).

## Solution

Separated fetch and process into two distinct phases - lines 459-465:
- Line 459: While loop closes (completes fetch phase)
- Line 465: Foreach begins (processes all rows once)
- Each template line now processes exactly 1 time

## Impact

- ✅ Multi-line QE templates process correctly (each line once)
- ✅ Reduce-base calculations work properly (no cascading)
- ✅ Total amounts now match template percentages (100% = 100%)
- ✅ First line no longer duplicated

## Files Changed

- includes/includes.inc - qe_to_cart() function refactoring
