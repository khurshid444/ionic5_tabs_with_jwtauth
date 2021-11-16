import {Platform} from '@ionic/angular';
import {Injectable} from '@angular/core';
import {Storage} from '@ionic/storage';
import {map, switchMap, take, tap} from 'rxjs/operators';
import { BehaviorSubject, Observable, from, of } from 'rxjs';
import {JwtHelperService} from '@auth0/angular-jwt';
import {HttpClient, HttpHeaders} from '@angular/common/http';
import {Router} from '@angular/router';
import {ConnectService} from './connect.service';
import { UcontrollerService } from './ucontroller.service';

const helper = new JwtHelperService();
const TOKEN_KEY = 'jwt-token';

@Injectable({
  providedIn: 'root'
})
export class AuthService {

  public user: any;
  private tokenData = new BehaviorSubject(null);

  constructor(
    private storage: Storage,
    private http: HttpClient,
    private plt: Platform,
    private router: Router,
    private ucontroller: UcontrollerService,
    public connect: ConnectService
    ) {
    this.ngOnInit();
    this.loadStoredToken();
  }

  async ngOnInit() {
    await this.storage.create();
  }


  loadStoredToken() {
    const platformObs = from(this.plt.ready());
    this.user = platformObs.pipe(
      switchMap(() => {
        return from(this.storage.get(TOKEN_KEY));
      }),
      map(token => {
        if (token) {
          const decoded = helper.decodeToken(token);
          this.tokenData.next(decoded);
          return true;
        } else {
          return null;
        }
      })
      );
  }

  login(credentials) {
    return this.http.post(this.connect.apiUrl + 'validateotp',credentials).pipe(
      take(1),
      map((res: any) => {
        return res
      }),
      tap(token => {
        console.log("TAP TOKEN",token);
        if(token.Data.status){
          this.storage.set('token', token.Data.token).then(() => {
            console.log('Token Stored');
          },
          error => console.error('Error storing item', error)
          );
          return token.Data.token;
        }
      }),
      switchMap(res => {
        console.log("switchMap RES",res);
        if(res.Data.status){
          const decoded = helper.decodeToken(res.Data.token);
          this.tokenData.next(decoded);
          this.connect.status = res.Data.status;
          this.storage.set('userData', res.Data.userid).then(() => {
          }, error => console.error('Error storing item', error));
          console.log("RETURNING switchMap")
          return from(this.storage.set(TOKEN_KEY, res.Data.token));
        }else{
          this.ucontroller.showToast(res.Data.message);
          return of(null);
        }
      })
      );

  }

  getTokenData(){
    return this.tokenData.getValue();
  }

  getUser(){
    if (this.storage.get('user_token')){
      return this.storage.get('user_token');
    } else {
      this.router.navigateByUrl('/login').then();
    }
  }

  logout() {
    this.storage.remove('user_token');
    this.storage.remove('token');
    this.storage.remove(TOKEN_KEY).then(() => {
      this.tokenData.next(null);
      this.router.navigateByUrl('/');
    });
  }

  get windowRef() {
    return window
  }
}