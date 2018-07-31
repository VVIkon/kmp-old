import { Pipe, PipeTransform } from '@angular/core';

@Pipe({ name: 'substr' })
export class SubstrPipe implements PipeTransform {
  transform(value: string, start: number, length?: number) {
    if (!value || !start) {
      return value;
    }

    return value.substr(start, length);
  }
}
