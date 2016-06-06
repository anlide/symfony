var app = angular.module('symfonyCult', []);
app.controller('AuthController', [ '$http', function($http){
  this.email = null;
  this.password = null;
  this.exists = null;
  this.showLogin = function() {
    return this.exists !== false;
  };
  this.allowLogin = function() {
    return this.exists === true;
  };
  this.allowRegister = function() {
    return ((this.exists === false) && (typeof this.email != 'undefined'));
  };
  this.onChangeEmail = function() {
    if (typeof this.email == 'undefined') {
      this.exists = false;
      return;
    }
    var self = this;
    $http.get('/login-exists=' + this.email).success(function(data){
      self.exists = data;
    });
  };
  this.onBtnLoginOauth = function(url) {
    window.location.href = url;
  };
  this.onLogin = function() {
    if (this.email === null) return;
    if (typeof this.email == 'undefined') return;
    if (this.password === null) return;
    $http.get('/login-check=' + this.email + '/' + this.password).success(function(data){
      if (data) {
        window.location.replace("/");
      } else {
        alert('invalid login');
      }
    });
  };
  this.onRegistrer = function() {
    $http.post('/register', { email: this.email, password: this.password }).success(function(data){
      window.location.replace("/");
    });
    console.log('onRegistrer');
  };
} ]);
app.controller('UserController', [ '$http', function($http){
  this.onLogout = function() {
    $http.get('/logout').success(function(data){
      window.location.replace("/");
    });
  };
  this.onProfile = function() {
    console.log('onProfile');
  };
} ]);
