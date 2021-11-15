import { NgModule } from '@angular/core';
import { PreloadAllModules, RouterModule, Routes } from '@angular/router';
import { AuthguardGuard } from './services/authguard.guard';

const routes: Routes = [
{ path: '', redirectTo: '/auth', pathMatch: 'full' },

{
	path: 'auth',
	loadChildren: () => import('./auth/auth.module').then(m => m.AuthPageModule)
},
{
	path: 'members',
	loadChildren: () => import('./tabs/tabs.module').then(m => m.TabsPageModule),
	canActivate: [AuthguardGuard],
},
  {
    path: 'register',
    loadChildren: () => import('./pages/register/register.module').then( m => m.RegisterPageModule)
  },

];

@NgModule({
	imports: [
	RouterModule.forRoot(routes, {
		preloadingStrategy: PreloadAllModules,
		/*  enableTracing: true, */
	}),
	],
	exports: [RouterModule],
})
export class AppRoutingModule {}
