import {Platform} from '@ionic/angular';
import {Injectable} from '@angular/core';
import {Storage} from '@ionic/storage';
import {BehaviorSubject, from} from 'rxjs';
import {map, switchMap, take, tap} from 'rxjs/operators';
import {JwtHelperService} from '@auth0/angular-jwt';
import {HttpClient} from '@angular/common/http';
import {Router} from '@angular/router';
import {ConnectService} from './connect.service';


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
    return this.http.post(this.connect.apiUrl + 'validateotp', credentials).pipe(
      take(1),
      map((res: any) => {
        return res
      }),
      tap(resp => {
        this.storage.set('token', resp.Data.token).then(() => {
          console.log('Token Stored');
        },
        error => console.error('Error storing item', error)
        );
        return resp;
      }),
      switchMap(res => {
        const decoded = helper.decodeToken(res.Data.token);
        this.tokenData.next(decoded);
        this.connect.status = res.Data.status;
        this.storage.set('user_token', res.Data.userid).then(() => {
        }, error => console.error('Error storing item', error));

        return from(this.storage.set(TOKEN_KEY, res.Data.token));
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