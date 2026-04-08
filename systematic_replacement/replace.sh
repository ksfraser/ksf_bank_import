# Systematic File Replacement Script
# Run this from the project root directory

WORKING_DIR="C:/Users/prote/Documents/bank_import"  # Set this to your working version directory
CURRENT_DIR="$(pwd)"

echo "Systematic File Replacement Tool"
echo "================================"
echo "Working directory: $WORKING_DIR"
echo "Current directory: $CURRENT_DIR"
echo ""

# Function to safely replace a file
replace_file() {
    local file="$1"
    local category="$2"
    local reason="$3"

    if [ ! -f "$WORKING_DIR/$file" ]; then
        echo "❌ Working version of $file not found"
        return 1
    fi

    if [ ! -f "$file" ]; then
        echo "⚠️  Current version of $file not found, creating"
    fi

    echo "🔄 Replacing $file (Category: $category)"
    echo "   Reason: $reason"

    # Backup current version
    if [ -f "$file" ]; then
        cp "$file" "$file.backup"
        echo "   Backup created: $file.backup"
    fi

    # Copy working version
    cp "$WORKING_DIR/$file" "$file"
    echo "   ✅ Replaced with working version"

    # Add to git
    git add "$file"

    # Log the replacement
    echo "## $(date)" >> systematic_replacement/REPLACEMENT_TRACKER.md
    echo "- **$file**: $category - $reason" >> systematic_replacement/REPLACEMENT_TRACKER.md
    echo "" >> systematic_replacement/REPLACEMENT_TRACKER.md
}

# Function to show diff before replacement
show_diff() {
    local file="$1"

    if [ -f "$WORKING_DIR/$file" ] && [ -f "$file" ]; then
        echo "=== DIFF for $file ==="
        diff -u "$file" "$WORKING_DIR/$file" | head -50
        echo "=== END DIFF ==="
    else
        echo "Cannot show diff for $file - missing file(s)"
    fi
}

# Function to list files that differ
list_differences() {
    echo "Files that differ between working and current versions:"
    echo "======================================================"

    find . -name "*.php" -type f | while read file; do
        # Remove leading ./
        clean_file="${file#./}"

        if [ -f "$WORKING_DIR/$clean_file" ]; then
            if ! cmp -s "$file" "$WORKING_DIR/$clean_file"; then
                echo "DIFF: $clean_file"
            fi
        else
            echo "NEW:  $clean_file"
        fi
    done
}

# Main menu
case "$1" in
    "list")
        list_differences
        ;;
    "diff")
        if [ -z "$2" ]; then
            echo "Usage: $0 diff <file>"
            exit 1
        fi
        show_diff "$2"
        ;;
    "replace")
        if [ -z "$2" ] || [ -z "$3" ]; then
            echo "Usage: $0 replace <file> <category> [reason]"
            echo "Categories: BROKEN_FUNCTIONALITY, COMPATIBILITY_ISSUES, REFACTORING_IMPROVEMENTS, NEW_FEATURES"
            exit 1
        fi
        replace_file "$2" "$3" "${4:-No reason provided}"
        ;;
    "commit")
        echo "Committing current replacements..."
        git commit -m "Systematic file replacement: $(git diff --cached --name-only | wc -l) files

$(git diff --cached --name-only | sed 's/^/- /')

See systematic_replacement/REPLACEMENT_TRACKER.md for details"
        ;;
    *)
        echo "Systematic File Replacement Tool"
        echo "================================"
        echo ""
        echo "Usage:"
        echo "  $0 list                    - List all files that differ"
        echo "  $0 diff <file>             - Show diff for a specific file"
        echo "  $0 replace <file> <cat>    - Replace file with working version"
        echo "  $0 commit                  - Commit current replacements"
        echo ""
        echo "Categories:"
        echo "  BROKEN_FUNCTIONALITY     - Bugs preventing operation"
        echo "  COMPATIBILITY_ISSUES     - PHP/environment issues"
        echo "  REFACTORING_IMPROVEMENTS - Better structure (keep if working)"
        echo "  NEW_FEATURES             - Additional functionality"
        echo ""
        echo "First, set WORKING_DIR variable to your working version path"
        ;;
esac