import { Component, Input, Output, ElementRef, EventEmitter, HostListener, OnInit } from '@angular/core';

@Component({
  selector: 'wgt-file-loader',
  host: {
    '[class.wgt-file-lodader]': 'true'
  },
  templateUrl: './tpl/wgt-file-loader.html'
})
export class FileLoaderWidget implements OnInit {
  private fileInputElement: HTMLInputElement;
  private file: File;

  @Output() onChange: EventEmitter<File|null> = new EventEmitter<File|null>();

  constructor(private el: ElementRef) { }

  ngOnInit(): void {
    this.fileInputElement = this.el.nativeElement.querySelector('input[type="file"');
  }

  onFileSelect(event: Event) {
    console.log('file changed');
    let target = <HTMLInputElement> event.target;
    let files = target.files;
    this.file = (files instanceof FileList && files[0] !== undefined) ? files[0] : null;
    this.onChange.emit(this.file);
  }

  deselectFile(event: Event) {
    event.stopPropagation();
    this.fileInputElement.value = '';
    this.fileInputElement.dispatchEvent(new Event('change'));
  }

 }
