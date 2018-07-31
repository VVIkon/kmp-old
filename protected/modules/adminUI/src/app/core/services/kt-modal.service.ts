import { Injectable } from '@angular/core';

interface IModal {
  id: string;
  open: Function,
  close: Function
};

@Injectable()
export class KtModalService {
    private modals = {};
    private openedModalId: string = null;

    add(modal: IModal) {
        // add modal to array of active modals
        this.modals[modal.id] = modal;
    }

    remove(id: string) {
      // remove modal from array of active modals
      delete this.modals[id];
    }

    open(id: string) {
      // open modal specified by id
      if (this.modals.hasOwnProperty(id)) {
        if (this.openedModalId !== null) {
          this.modals[this.openedModalId].close();
        }
        this.modals[id].open();
        this.openedModalId = id;
      }
    }

    close(id: string) {
      // close modal specified by id
      if (this.modals.hasOwnProperty(id)) {
        this.modals[id].close();
        if (this.openedModalId === id) {
          this.openedModalId = null;
        }
      }
    }
}
