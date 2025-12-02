class PermissionService {
    constructor() {
        this.userPermissions = null;
        this.userRoles = null;
        this.initializeFromWindow();
    }

    initializeFromWindow() {
        if (typeof window !== 'undefined' && window.userData) {
            this.setUserPermissions(window.userData.permissions, window.userData.roles);
            console.log('PermissionService initialized with permissions:', this.userPermissions);
        } else {
            console.log('PermissionService: window.userData not found');
        }
    }

    setUserPermissions(permissions, roles) {
        this.userPermissions = permissions || [];
        this.userRoles = roles || [];
    }

    hasPermission(permissionName) {
        if (!this.userPermissions) {
            this.initializeFromWindow();
        }
        const result = this.userPermissions.includes(permissionName);
        console.log(`PermissionService.hasPermission("${permissionName}"):`, result, 'Available permissions:', this.userPermissions);
        return result;
    }

    hasAnyPermission(permissionNames) {
        if (!Array.isArray(permissionNames)) {
            permissionNames = [permissionNames];
        }
        return permissionNames.some((permission) => this.hasPermission(permission));
    }

    hasAllPermissions(permissionNames) {
        if (!Array.isArray(permissionNames)) {
            permissionNames = [permissionNames];
        }
        return permissionNames.every((permission) => this.hasPermission(permission));
    }

    canCreate(moduleName) {
        return this.hasPermission(`${moduleName} Add`);
    }

    canRead(moduleName) {
        return this.hasPermission(`${moduleName} Show`) || this.hasPermission(`${moduleName} Detail`);
    }

    canUpdate(moduleName) {
        return this.hasPermission(`${moduleName} Edit`);
    }

    canDelete(moduleName) {
        return this.hasPermission(`${moduleName} Delete`);
    }

    can(moduleName, action) {
        return this.hasPermission(`${moduleName} ${action}`);
    }

    getUserPermissions() {
        if (!this.userPermissions) {
            this.initializeFromWindow();
        }
        return this.userPermissions || [];
    }

    getUserRoles() {
        if (!this.userRoles) {
            this.initializeFromWindow();
        }
        return this.userRoles || [];
    }

    refresh() {
        this.userPermissions = null;
        this.userRoles = null;
        this.initializeFromWindow();
    }
}

export default new PermissionService();
