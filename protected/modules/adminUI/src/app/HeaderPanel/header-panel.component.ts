import { Component } from '@angular/core';

import { ProfileService } from '../core/services/profile.service';

@Component({
  selector: 'header-panel',
  templateUrl: './header-panel.component.html'
})
export class HeaderPanelComponent {
  private user: {
    id: number;
    firstName?: string,
    lastName: string,
    middleName?: string,
  };

  constructor(private profileService: ProfileService) { }


  ngOnInit(): void {
    this.user = this.profileService.profile.user
  }
}
