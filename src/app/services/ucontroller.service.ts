import { Injectable } from '@angular/core';
import { ToastController,AlertController,LoadingController  } from '@ionic/angular';
import { BehaviorSubject } from 'rxjs';

@Injectable({
  providedIn: 'root'
})
export class UcontrollerService {
  myToast:any;
  loading;
  
  constructor( public toast: ToastController,public alertController: AlertController,public loadingController: LoadingController) { }

  showToast(response) {
    this.myToast = this.toast.create({
      message: response,
      duration: 3000
    }).then((toastData) => {
      // console.log(toastData);
      toastData.present();
    });
  }

  TopshowToast(response) {
    this.myToast = this.toast.create({
      message: response,
      position: 'middle',
      color: 'secondary',
      duration: 3000
    }).then((toastData) => {
      // console.log(toastData);
      toastData.present();
    });
  }

  async presentAlert(msg,response){
    const alert = await this.alertController.create({
      cssClass: 'my-custom-class',
      header: msg,
      // subHeader: 'Subtitle',
      message: response,
      buttons: ['OK']
    });

    await alert.present();
  }
  

  HideToast() {
    this.myToast = this.toast.dismiss();
  }


  presentLoading() {
    this.loadingController.create({
      cssClass: 'my-custom-class',
      message: 'Please wait...'
    }).then((res) => {
      res.present();
    });
  }

  hideLoader() {
    this.loadingController.dismiss().then((res) => {
      console.log('Loading dismissed!', res);
    }).catch((error) => {
      console.log('error', error);
    });
  }


  async show() {
    this.loading = await this.loadingController.create({
      spinner: 'bubbles',
      backdropDismiss: true
    });
    await this.loading.present();
  }

  async hide() {
    try {
      await this.loading.dismiss();
    } catch (error) {
    }
  }


}
