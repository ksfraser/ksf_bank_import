#!/usr/bin/env python3
"""
Verify that pagination updates were applied to process_statements.php
"""

import os
import sys

file_path = 'process_statements.php'

# Check if file exists
if not os.path.exists(file_path):
    print(f"ERROR: {file_path} not found")
    sys.exit(1)

# Read the file
with open(file_path, 'r', encoding='utf-8', errors='ignore') as f:
    content = f.read()

# Check for pagination markers
checks = {
    'current_page': 'current_page' in content,
    'page_size': 'page_size' in content,
    'offset': ('$offset' in content or 'offset' in content),
    'limit_page': ('$limit_page' in content or 'limit_page' in content),
    'setPaginationData': 'setPaginationData' in content,
}

print("=== Pagination Update Verification ===\n")
print(f"File: {file_path}")
print(f"File size: {len(content)} bytes")
print(f"First 200 chars: {content[:200]}")
print(f"\nPagination markers found:")
for marker, found in checks.items():
    status = "✓" if found else "✗"
    print(f"  {status} {marker}")

total_found = sum(checks.values())
print(f"\nResult: {total_found}/{len(checks)} pagination markers found")

if total_found < len(checks):
    print("\n⚠️  Process_statements.php was NOT updated with pagination")
    print("Need to manually apply updates")
else:
    print("\n✓ Process_statements.php appears to have pagination updates")
