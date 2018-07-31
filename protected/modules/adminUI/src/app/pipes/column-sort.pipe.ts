import { Pipe, PipeTransform } from '@angular/core';

@Pipe({ name: 'columnsort' })
export class ColumnSortPipe implements PipeTransform {
  transform(value: any[], columns: number) {
    if (!value || !columns || (columns === 1)) {
      return value;
    }

    let sorted: any[] = [];
    let columnSize = Math.floor(value.length / 3);

    for (let i = 0; i < columns; i++) {
      [].push.apply(sorted, value.slice(i * columnSize, (i + 1) * columnSize));
    }
    [].push.apply(sorted, value.slice(columns * columnSize));
    return sorted;
  }
}
