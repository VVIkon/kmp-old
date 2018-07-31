import { Component, Input, Output, EventEmitter, HostListener, OnInit } from '@angular/core';
import { Observable } from 'rxjs/Observable';

interface Option {
  value: string;
  name: string;
  object: object;
};

enum WidgetState {
  Loading,
  Default
};

@Component({
  selector: 'wgt-select',
  host: {
    '[class.wgt-select]': 'true'
  },
  templateUrl: './tpl/wgt-select.html'
})
export class SelectWidget implements OnInit {
  private wgtId: string;
  private options: Option[];
  private selectedOption: Option;
  private isFocused: boolean;
  private WidgetState = WidgetState;
  private currentState: WidgetState = WidgetState.Default;

  @Input() valueField = 'value';
  @Input() labelField = 'name';
  @Input() fullWidth = false;
  @Input() baseValue: string;

  @Input() set data(data: object[]) {
    if (!data) {
      this.options = null;
    } else {
      this.options = data.map((item): Option => {
        return {
          value: item[this.valueField],
          name: item[this.labelField],
          object: item
        }
      });

      if (this.baseValue !== undefined && !this.selectedOption) {
        let options = this.options.filter((item): boolean => {
          return (item.value === this.baseValue);
        });
        if (options.length > 0) {
          this.selectedOption = options[0];
          // this.onChange.emit(this.selectedOption); // - зачем мне тут это?
        }
      }
    }
  };

  @Input() set state(state: string) {
    switch (state) {
      case 'loading':
        this.currentState = WidgetState.Loading;
        break;
      default:
        this.currentState = WidgetState.Default;
    }
  }

  @Output() onType: EventEmitter<string> = new EventEmitter<string>();
  @Output() onChange: EventEmitter<object> = new EventEmitter<object>();

  constructor() { }

  ngOnInit(): void {
    this.wgtId = Math.random().toString().substr(20);
    this.isFocused = false;
  }

  toggleDropdown(state?: boolean) {
    this.isFocused = (state !== undefined) ? state : !this.isFocused;
  }

  onOptionSelect(option: Option): void {
    this.selectedOption = option;
    this.toggleDropdown(false);
    this.onChange.emit(option.object);
  }

  onKey(event: Event) {
    let target = <HTMLInputElement> event.target;
    if (this.selectedOption) {
      this.onChange.emit(null);
    }
    this.selectedOption = null;
    this.onType.emit(target.value);
  }

  onBlur(): void {
    this.toggleDropdown(false);
  }

  /*
  @HostListener('click', ['$event']) onHostClick(event: Event) {
    event['wgt_select'] = this.wgtId;
  }
  */

  /*
  @HostListener('body:click', ['$event']) onOutOfHostClick(event: Event) {
    if (!event.hasOwnProperty('wgt_select') || event['wgt_select'] !== this.wgtId) {
      this.isFocused = false;
    }
  }
  */
 }
