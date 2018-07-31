export interface IUserRole {
  roleId: number;
  permissionsName: string;
  permissionsCode: string;
  permissionsCodeHex: string;
};

export class UserRole {
  id: number;
  name: string;
  mask: string;
  hexmask: string;

  constructor (role: IUserRole) {
    this.id = role.roleId;
    this.name = role.permissionsName;
    this.mask = role.permissionsCode;
    this.hexmask = role.permissionsCodeHex;
  }

  toStruct(): IUserRole {
    return {
      roleId: this.id,
      permissionsName: this.name,
      permissionsCode: this.mask,
      permissionsCodeHex: this.hexmask
    };
  }
}
