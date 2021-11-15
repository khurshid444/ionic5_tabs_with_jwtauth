import { Component, OnInit } from '@angular/core';
import { UserDetailService } from '../../services/user-detail.service';
import { UcontrollerService } from '../../services/ucontroller.service';
import { ConnectService} from '../../services/connect.service';
import { AuthService} from '../../services/auth.service';

@Component({
	selector: 'app-dashboard',
	templateUrl: './dashboard.page.html',
	styleUrls: ['./dashboard.page.scss'],
})
export class DashboardPage implements OnInit {

	userData = {};
	user_token;
	userrole;
	constructor(private auth: AuthService,public chatConnect:ConnectService,private ucontroller: UcontrollerService,private userDetailService: UserDetailService) {
		this.user_token = this.auth.getTokenData();
		this.userData["user_token"] = this.user_token;
		console.log(this.user_token);
		// this.userrole = this.userDetailService.getUserrole();
	}


	ngOnInit() {}
}
