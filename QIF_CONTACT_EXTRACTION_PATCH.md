QIF Contact Extraction Patch

Purpose
- Add conservative contact extraction to the local QIF wrapper `qif_parser.php` so parsed statements include a `contact` object that host importers can consume.
- This is intended for the external qifparser repo (do not change vendor package internals here).

Patch (unified diff)

--- a/vendor/ksfraser/qifparser/qif_parser.php
+++ b/vendor/ksfraser/qifparser/qif_parser.php
@@
-    private function mapTransaction($qifTrz, $static_data): object {
-        // existing mapping logic...
-        $smt = (object) [
-            'date' => $qifTrz->date ?? null,
-            'amount' => $qifTrz->amount ?? 0,
-            'payee' => $qifTrz->payee ?? '',
-            'memo' => $qifTrz->memo ?? '',
-            // ...other fields
-        ];
-        return $smt;
-    }
+    private function mapTransaction($qifTrz, $static_data): object {
+        // existing mapping logic...
+        $smt = (object) [
+            'date' => $qifTrz->date ?? null,
+            'amount' => $qifTrz->amount ?? 0,
+            'payee' => $qifTrz->payee ?? '',
+            'memo' => $qifTrz->memo ?? '',
+            // ...other fields
+        ];
+
+        // --- Contact extraction (new) ---
+        // Build a lightweight ContactData object that the host importer can consume.
+        // Keep this conservative: populate what is available in QIF record (payee/memo).
+        $rawName = trim((string) ($qifTrz->payee ?? ''));
+        $rawMemo = trim((string) ($qifTrz->memo ?? ''));
+
+        $contact = (object) [
+            // normalized display name (host may further normalize)
+            'name' => $rawName !== '' ? $rawName : null,
+            // raw fields for downstream heuristics
+            'raw' => $rawName . ($rawMemo !== '' ? ' — ' . $rawMemo : ''),
+            'payee' => $rawName,
+            'memo' => $rawMemo,
+            // placeholders for optional fields the host may fill or match on
+            'email' => null,
+            'phone' => null,
+            'metadata' => (object)[],
+        ];
+
+        // Attach contact to the statement object for the importer to consume
+        $smt->contact = $contact;
+
+        return $smt;
+    }


Suggested Unit Test (tests/Unit/QifParserContactExtractionTest.php)

<?php
use PHPUnit\Framework\TestCase;

class QifParserContactExtractionTest extends TestCase
{
    public function test_map_transaction_includes_contact()
    {
        $qif = "D01/01/20\nT-25.00\nPExample Merchant\n^";
        $parser = new QIF_parser();
        $stmts = $parser->parse($qif, [], true);
        $this->assertNotEmpty($stmts);
        $first = $stmts[0];
        $this->assertObjectHasAttribute('contact', $first);
        $this->assertNotNull($first->contact->name);
        $this->assertStringContainsString('Example Merchant', $first->contact->payee);
    }
}


Apply instructions for the external repo (what to feed the other agent)

1) Create feature branch

   git checkout -b feat/contact-extraction

2) Apply the patch

- Option A: Save the unified diff above as `qif_contact.patch` and run:

   git apply qif_contact.patch

- Option B: Edit `vendor/ksfraser/qifparser/qif_parser.php` and replace the `mapTransaction()` body per patch.

3) Add the unit test file at `tests/Unit/QifParserContactExtractionTest.php` (content above).

4) Run tests locally

   composer install
   vendor/bin/phpunit --filter QifParserContactExtractionTest

5) If green, push branch and open a PR

   git add -A
   git commit -m "feat: add contact extraction to qif wrapper (mapTransaction)")
   git push -u origin feat/contact-extraction

Notes and guidance
- This change is intentionally conservative: it only exposes available payee/memo data as `contact` for host importers to use.
- The host project should do normalization, deduplication and matching using its `ContactMatchingService`.
- Do not modify underlying upstream package unless you intend to submit the wrapper changes upstream as a separate PR or propose API changes.

Host-repo next-small-refactor (what I recommend you do next)

- Add handling in `import_statements.php::importStatement()` to read `$smt->contact` (if present), convert to host `ContactData` (use `ContactDataFactory::fromParser()`), call `ContactMatchingService::findOrCreate()`, and attach returned `contact_id` to the imported statement payload before DB insert.

- This is a small, single-function change in the host repo that unlocks automatic contact linking without schema changes.

Checklist for the external agent to run

- [ ] Create feature branch
- [ ] Apply patch or edit file
- [ ] Add unit test
- [ ] Run unit tests
- [ ] Push + open PR


End of file
