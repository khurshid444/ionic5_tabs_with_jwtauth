import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable, of } from 'rxjs';
import { tap, map } from 'rxjs/operators';
import {RespuestaTopHeadlines} from '../interface';



const  apiKey= '13543b43dd0f4d819b36fdda8d4cb071';
const newsapiUrl= 'https://newsapi.org/v2';



const headers = new HttpHeaders({
  'X-Api-key': apiKey
});



@Injectable({
  providedIn: 'root'
})
export class ConnectService {
 apiUrl = "http://localhost/earnsapi/";

 public status: {
  active: any,
  inactive: any,
  sold: any,
  expired: any;
};

currentNews: any;
jData:any;
userData={};
mydata:any;
data:any; 
contacts:[];

headlinesPage = 0;

categoriaActual = '';
categoriaPage = 0;

constructor(private http: HttpClient) {
  console.log('Hello ChatconnectProvider Provider');
}

private ejecutarQuery<T>(query: string){
  query = newsapiUrl + query;
  return this.http.get<T>(query, {headers});
}




postData(credentials, type) {
  return new Promise((resolve, reject) => {
    this.http.post(this.apiUrl+type, JSON.stringify(credentials)).subscribe(res => {                                          
      resolve(res);
    }, (err) =>{
      reject(err);
      console.log("In Chatconnect provider : Error");
    });
  });
} 




getTopHeadlines(){
  this.headlinesPage++;
  return this.ejecutarQuery<RespuestaTopHeadlines>(`/top-headlines?country=in&page=${this.headlinesPage}`);
    //return this.http.get<RespuestaTopHeadlines>(`https://newsapi.org/v2/top-headlines?country=us&apiKey=13543b43dd0f4d819b36fdda8d4cb071`);
  }

  getTopCategory(){
    this.headlinesPage++;
    return this.ejecutarQuery<RespuestaTopHeadlines>(`/top-headlines?country=in&category=business&page=${this.headlinesPage}`);

    //return this.http.get<RespuestaTopHeadlines>(`https://newsapi.org/v2/top-headlines?country=us&apiKey=13543b43dd0f4d819b36fdda8d4cb071`);
  }

  getTopHeadlinesCategoria(categoria: string){

    if(this.categoriaActual === categoria) {
      this.categoriaPage++;
    }else {
      this.categoriaPage = 1;
      this.categoriaActual = categoria;
    }

    // return this.ejecutarQuery<RespuestaTopHeadlines>(`/top-headlines?country=in&category=${categoria}&page=${this.categoriaPage}`);
    return this.ejecutarQuery<RespuestaTopHeadlines>(`/top-headlines?country=in&category=business&page=${this.categoriaPage}`);
    //this.http.get(`https://newsapi.org/v2/top-headlines?country=de&category=business&apiKey=13543b43dd0f4d819b36fdda8d4cb071`);
  }

}
