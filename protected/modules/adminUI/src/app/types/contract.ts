export interface IContract {
  ContractID: number;
  ContractID_UTK: string;
  ContractDate: string;
  expired: boolean;
  ContractExpiry?: string;
};

export class Contract {
  id: number;
  utkId: string;
  date: string;
  expired: boolean;
  expiryDate: string;

  constructor (contract: IContract) {
    this.id = contract.ContractID;
    this.utkId = contract.ContractID_UTK;
    this.date = contract.ContractDate;
    this.expired = contract.expired;
    this.expiryDate = contract.ContractExpiry;
  }
}
