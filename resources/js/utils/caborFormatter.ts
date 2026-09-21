export const formatCaborWithIcon = (cabor: { nama?: string; icon?: string | null } | string | null): string => {
    if (!cabor) return '-';
    
    const caborNama = typeof cabor === 'string' ? cabor : cabor.nama || '-';
    const caborIcon = typeof cabor === 'string' ? null : cabor.icon;
    
    if (!caborIcon) {
        return caborNama;
    }
    
    // Format icon class
    const iconClass = caborIcon.startsWith('fa-') ? caborIcon : `fa-${caborIcon}`;
    
    return `
        <div class="flex items-center gap-2">
            <i class="fa-solid ${iconClass} text-sm text-muted-foreground"></i>
            <span>${caborNama}</span>
        </div>
    `;
};

