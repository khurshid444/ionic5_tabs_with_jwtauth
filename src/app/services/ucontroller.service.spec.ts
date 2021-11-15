import { TestBed } from '@angular/core/testing';

import { UcontrollerService } from './ucontroller.service';

describe('UcontrollerService', () => {
  let service: UcontrollerService;

  beforeEach(() => {
    TestBed.configureTestingModule({});
    service = TestBed.inject(UcontrollerService);
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });
});
