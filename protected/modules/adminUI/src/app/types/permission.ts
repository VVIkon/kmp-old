export interface IPermission {
  bit: number;
  permission: string;
};

export class Permission {
  bit: number;
  name: string;
  set: boolean;

  constructor (permission: IPermission) {
    this.bit = permission.bit;
    this.name = permission.permission;
    this.set = false;
  }

  allow() {
    this.set = true;
  }

  forbid() {
    this.set = false;
  }

  toStruct(): IPermission {
    return {
      bit: this.bit,
      permission: this.name
    };
  }
}
