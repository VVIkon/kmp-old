import { Directive, ElementRef, HostListener, Input } from '@angular/core';

// import all svg icons for sprite creation
let context = require.context('../../../assets/img/kt-icons', true, /.+\.svg?$/);
context.keys().forEach(context);

@Directive({
  selector: '[kt-icon]'
})
export class KtIconDirective {

  @Input('kt-icon') iconType: string;

  constructor(private el: ElementRef) { }

  ngOnInit(): void {
    let icon = this.el.nativeElement;
    icon.classList.add('kt-icon');
    // doen;t work via appendChild or appendChildNS o_O
    icon.innerHTML = '<use xlink:href="#' + this.iconType + '" />';
    // let use = document.createElementNS('http://www.w3.org/2000/svg', 'use');
    // use.setAttribute('xlink:href', '#' + this.iconType);
    // icon.appendChild(use);
  }
}
