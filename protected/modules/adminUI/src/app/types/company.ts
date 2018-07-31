import { Contract, IContract } from './contract';

export interface ICompany {
  companyId: number;
  name: string;
  companyRoleType: number;
  INN: number;
  Contracts: IContract[];
};

export class Company {
  id: number;
  name: string;
  roleType: number;
  inn: number;
  contracts: Contract[];

  constructor (company: ICompany) {
    this.id = company.companyId;
    this.name = company.name;
    this.roleType = company.companyRoleType;
    this.inn = company.INN;
    this.contracts = company.Contracts.map((contract: IContract): Contract => {
      return new Contract(contract);
    })
  }
}
