#!/usr/bin/env python3
import os

root = os.path.join(os.path.dirname(__file__), "..", "resources", "js")

replacements = [
    (
        "w-12 h-12 bg-gray-200 rounded-full flex items-center justify-center text-gray-500 text-xs",
        "flex h-12 w-12 items-center justify-center rounded-full bg-muted text-xs text-muted-foreground",
    ),
    (
        "h-16 w-16 bg-gray-200 rounded flex items-center justify-center text-gray-500 text-xs",
        "flex h-16 w-16 items-center justify-center rounded bg-muted text-xs text-muted-foreground",
    ),
    (
        'class="bg-background relative inline-flex h-5 w-5 cursor-pointer items-center justify-center rounded border border-gray-500"',
        'class="table-checkbox"',
    ),
    (
        "class='bg-background relative inline-flex h-5 w-5 cursor-pointer items-center justify-center rounded border border-gray-500'",
        "class='table-checkbox'",
    ),
    (
        '<label class="bg-background relative inline-flex h-5 w-5 cursor-pointer items-center justify-center rounded border border-gray-500">',
        '<label class="table-checkbox">',
    ),
    ("text-black dark:text-white", "text-foreground"),
    ("text-gray-400", "text-muted-foreground"),
    ("text-gray-500", "text-muted-foreground"),
    ("text-gray-600", "text-muted-foreground"),
    ("text-gray-700", "text-foreground"),
    ("bg-gray-100 text-gray-800", "badge-muted"),
    ("bg-gray-300 text-gray-600", "badge-muted"),
    ("bg-gray-300 text-gray-700", "badge-muted"),
    ("bg-gray-500 text-white", "bg-primary text-primary-foreground"),
    ("bg-gray-400 text-white", "bg-muted-foreground text-primary-foreground"),
    ("return 'bg-gray-100 text-gray-800'", "return 'badge-muted'"),
    ("bg-blue-100 text-blue-800 hover:bg-blue-200", "stat-chip stat-chip-atlet hover:opacity-90"),
    ("bg-green-100 text-green-800 hover:bg-green-200", "stat-chip stat-chip-pelatih hover:opacity-90"),
    ("bg-yellow-100 text-yellow-800 hover:bg-yellow-200", "stat-chip stat-chip-tenaga hover:opacity-90"),
    ("text-green-800 bg-green-100 rounded-full", "badge-success"),
    ("text-green-800 bg-green-100", "badge-success"),
    ("text-yellow-800 bg-yellow-100 rounded-full", "badge-warning"),
    ("text-yellow-800 bg-yellow-100", "badge-warning"),
    ("text-blue-800 bg-blue-100 rounded-full", "stat-chip stat-chip-atlet"),
    (
        "inline-flex items-center px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full",
        "badge-role inline-flex items-center px-2 py-1 text-xs font-medium rounded-full",
    ),
    (
        "inline-flex items-center px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full mr-1 mb-1",
        "badge-role inline-flex items-center px-2 py-1 text-xs font-medium rounded-full mr-1 mb-1",
    ),
    ("border-green-200 bg-green-50 p-4", "alert-success"),
    ("border-blue-200 bg-blue-50 p-4", "rounded-lg border border-border bg-muted p-4"),
    ("border-blue-300 bg-blue-50", "rounded-md border border-border bg-muted"),
    ("bg-gray-200 dark:bg-gray-700", "bg-muted"),
    ("text-green-500", "text-[var(--success-foreground)]"),
    ("bg-gray-500", "bg-muted-foreground"),
    ("mendekati_target: 'bg-blue-100 text-blue-800'", "mendekati_target: 'stat-chip stat-chip-atlet'"),
    ("Selesai: 'bg-blue-100 text-blue-800'", "Selesai: 'stat-chip stat-chip-atlet'"),
    ("bg-indigo-100 text-indigo-800 hover:bg-indigo-200", "stat-chip stat-chip-atlet hover:opacity-90"),
]

count = 0
for dirpath, _, filenames in os.walk(root):
    for fn in filenames:
        if not fn.endswith((".vue", ".ts")):
            continue
        path = os.path.join(dirpath, fn)
        with open(path, encoding="utf-8") as f:
            content = f.read()
        orig = content
        for old, new in replacements:
            content = content.replace(old, new)
        if content != orig:
            with open(path, "w", encoding="utf-8") as f:
                f.write(content)
            count += 1
            print(path)

print(f"Updated {count} files")
