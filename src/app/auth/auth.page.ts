import { Component, OnInit } from '@angular/core';
import { FormGroup, FormBuilder, Validators } from "@angular/forms";
import { UcontrollerService } from '../services/ucontroller.service';
import { ConnectService} from '../services/connect.service';
import { AuthService} from '../services/auth.service';
import { UserDetailService } from '../services/user-detail.service';
import { Router } from '@angular/router';
import { NavController } from '@ionic/angular';
import { InAppBrowser } from '@ionic-native/in-app-browser/ngx';


@Component({
  selector: 'app-auth',
  templateUrl: './auth.page.html',
  styleUrls: ['./auth.page.scss'],
})
export class AuthPage implements OnInit {
  ionicForm: FormGroup;
  userData = {};
  jData:any;
  flag=0;
  isSubmitted = false;
  mobile:any; 
  mobileotp:any;  
  countrystatus:any;  
  isenabled:boolean=false;
  disableButton;

  constructor(private iab: InAppBrowser,private _navController: NavController,public formBuilder: FormBuilder,public chatConnect:ConnectService,private ucontroller: UcontrollerService,private userDetailService: UserDetailService,private _router: Router,private auth: AuthService) { }

  ngOnInit() {
    this.ionicForm = this.formBuilder.group({
      mobile: ['', [Validators.required, Validators.pattern('^[0-9]+$')]],
      mobileotp: ['', [Validators.required]]
    })
    this.mobileotp = this.ionicForm.controls['mobileotp'];
    this.mobile = this.ionicForm.controls['mobile'];
  }

  get errorControl() {
    return this.ionicForm.controls;
  }

  submitForm() {
    this.isSubmitted = true;
    if (!this.ionicForm.valid) {
      console.log('Please provide all the required values!')
      return false;
    } else {
      this.userData["contact"] =this.ionicForm.value.mobile;        
      this.userData["otp"] = this.ionicForm.value.mobileotp;
      this.auth.login(this.userData).subscribe(
        async res => {
          console.log("AUH RES --->",res);
          await this.ucontroller.hide();
          this.disableButton = false;
          if (res) {
            this.auth.getUser().then(user =>{
              console.log(user);
            });
            await this._router.navigateByUrl('/members/dashboard');
          } else {
            // await this.ucontroller.showToast('Login Failed');         
          }
        }, error => {
          console.log(error);
          this.ucontroller.hide();
          this.disableButton = false;
          // this.ucontroller.showToast('Login Failed');         
        }
        );


/*      this.chatConnect.postData(this.userData, "validateotp").then((result) => {
        this.jData = result;
        console.log(this.jData);
        if(this.jData.Data.status == 1){
          this.userDetailService.setUserData(this.jData.Data.userid,this.jData.Data.role);
          // this.userDetailService.login(this.jData.Data.userid);
          this._navController.navigateRoot('/members/dashboard');
        }else{
          if(this.jData.Data.status ==3){
            let mobcount="Invalid Otp";
            this.ucontroller.showToast(mobcount);          
          }else{            
            let mobcount="Unauthorised user. Please Contact the Admin.";
            this.ucontroller.showToast(mobcount);         
          }
        }
      }, (err) => {
        console.log(err);
        console.log("Connection failed Messge");
      });*/

    }
  }

  verifythis(){
    let mobcount=this.mobile.value.toString().length;
    if(mobcount == 10){
      this.ucontroller.showToast("Please enter the OTP");
      this.flag=1;                    
      this.userData["contact"] = this.ionicForm.value.mobile;
      console.log(this.userData);
      this.chatConnect.postData(this.userData, "login").then((result) => {
        this.jData = result;
        console.log(this.jData);
        if(this.jData.Data.status == 0){
          let msg="Oops !";
          let tstmsg=this.jData.Data.response;
              // this.ucontroller.presentAlert(msg,tstmsg);
              // this._router.navigate(['/login']);
              this.ucontroller.showToast(this.jData.Data.response);         
              this.flag=0;
            }else{            
              // this.ucontroller.showToast("Valid USer");          
            }
          }, (err) => {
            console.log(err);
            console.log("Connection failed Messge");
          });

    }else{
      this.ucontroller.showToast("Please enter the proper number");         
    }

  }

  onSelectionChange(event){
    console.log(event.detail.checked);
    if(event.detail.checked==true){
      this.isenabled=true; 
    }else{
      this.isenabled=false; 
    }
  }

  Openthis(val){
    console.log(val);
    if (val==1) {
      let url1 ="https://market.webinovator.com/term.php";
      const browser = this.iab.create(url1,'_self',{location:'no'}); 
    } else if(val==2) {
      let url2 ="https://market.webinovator.com/privacy.php";
      const browser = this.iab.create(url2,'_self',{location:'no'}); 
    }else{
      let url3 ="https://market.webinovator.com/cookies.php";
      const browser = this.iab.create(url3,'_self',{location:'no'}); 

    }
  }
}
