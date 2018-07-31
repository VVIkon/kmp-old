import { Component, ElementRef, ApplicationRef, Input, OnInit, OnDestroy } from '@angular/core';

import { KtModalService } from '../services/kt-modal.service';

@Component({
  moduleId: module.id.toString(),
  selector: 'kt-modal',
  host: {
    '[class.kt-modal-overlay]': 'true'
  },
  template: `
    <div class="kt-modal-overlay__tbl">
      <div class="kt-modal-overlay__cell">
        <div
          class="kt-modal-overlay__wrapper kt-modal js-kt-modal"
          [ngClass]="size ? 'kt-modal--'+size : ''"
          [ngClass]="scroll ? 'kt-modal--scroll-'+scroll : ''"
        >
          <div *ngIf="header" class="kt-modal__header">{{header}}</div>
          <ng-content></ng-content>
        </div>
      </div>
    </div>`
})

export class KtModalComponent implements OnInit, OnDestroy {
  private appElementRef: ElementRef;
  private element: HTMLElement;

  @Input() id: string;
  @Input() header: string;
  @Input() size: string;
  @Input() scroll: string;

  constructor(
    private modalService: KtModalService,
    private el: ElementRef,
    private applicationRef: ApplicationRef
  ) {
    this.element = el.nativeElement;
    this.appElementRef = applicationRef.components[0].location;
  }

  ngOnInit(): void {
    let modal = this;

    // ensure id attribute exists
    if (!this.id) {
      console.error('modal must have an id');
      return;
    }

    // move element to bottom of application
    this.element = this.appElementRef.nativeElement.appendChild(this.element);

    // close modal on background click
    this.element.onclick = function (e: any) {
      let node = e.target;

      while (node) {
        if (node.classList.contains('js-kt-modal')) {
          return;
        }
        node = node.parentElement;
      }

      modal.close();
    };

    // add self (this modal instance) to the modal service so it's accessible from controllers
    this.modalService.add(this);
  }

  // remove self from modal service when directive is destroyed
  ngOnDestroy(): void {
    this.modalService.remove(this.id);
    this.element.parentElement.removeChild(this.element);
  }

  // open modal
  open(): void {
    this.element.classList.add('is-active');
    // document.body.classList.add('modal-open');
  }

  // close modal
  close(): void {
    this.element.classList.remove('is-active');
    // document.body.classList.remove('modal-open');
  }
}
