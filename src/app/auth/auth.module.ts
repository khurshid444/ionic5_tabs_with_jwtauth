import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { Routes, RouterModule } from '@angular/router'; 

import { IonicModule } from '@ionic/angular';
import { InAppBrowser } from '@ionic-native/in-app-browser/ngx';
import { AuthPage } from './auth.page';

const routes: Routes = [
{
  path: '',
  component: AuthPage
}
];

@NgModule({
  imports: [
  CommonModule,
  FormsModule,
  ReactiveFormsModule,
  IonicModule,
  RouterModule.forChild(routes)
  ],
  providers: [InAppBrowser],
  declarations: [AuthPage]
})
export class AuthPageModule {}
