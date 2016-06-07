var app = angular.module('symfonyCult', []);
app.config(function($interpolateProvider) {
  $interpolateProvider.startSymbol('[[');
  $interpolateProvider.endSymbol(']]');
});
app.controller('AuthController', [ '$http', function($http){
  this.email = '';
  this.password = '';
  /**
   * Текст кнопки, важно знать, что в конце контроллера дёргается метод "_updateLoginButton".
   * @type {string}
   */
  this.buttonLoginText = 'Укажите Email';
  /**
   * null - Ещё не спрашивали сервер
   * true - при последнем запросе указанный email существовал
   * false - при последнем запросе указанный email не существовал
   * @type {null|boolean}
   */
  this.emailExists = null;
  /**
   * 0 - Без уведомлений
   * 1 - Неверный пароль при попытке логина
   * 2 - Код регистрации не введён
   * 3 - Пароля нет (когда был вход только через соц сеть)
   * @type {number}
   */
  this.alertType = 0;
  /**
   * 1 - Форма входа
   * 2 - Форма восстановления
   * 3 - Форма завершения регистрации
   * @type {number}
   */
  this.form = 1;
  /**
   * На форме логина - как подсвечивать главную кнопку
   * @returns {boolean}
   */
  this.showPrimary = function() {
    if (typeof this.email == 'undefined') return false;
    if (typeof this.password == 'undefined') return false;
    if (this.password == '') return false;
    return true;
  };
  this.onChangeEmail = function() {
    this._updateLoginButton();
    if (typeof this.email == 'undefined') return;
    var self = this;
    $http.get('/login-exists=' + this.email).success(function(data){
      self.emailExists = data;
      self._updateLoginButton();
    });
  };
  this.onChangePassword = function() {
    this._updateLoginButton();
  };
  this._updateLoginButton = function() {
    if (typeof this.email == 'undefined') {
      this.buttonLoginText = 'Укажите Email';
      return;
    }
    if ((this.password == '') || (typeof this.password == 'undefined') || (this.password === null)) {
      this.buttonLoginText = 'Укажите пароль';
      return;
    }
    if (this.emailExists) {
      this.buttonLoginText = 'Вход';
    } else {
      this.buttonLoginText = 'Регистрация';
    }
  };
  this.onBtnLoginOauth = function(url) {
    window.location.href = url;
  };
  this.onLogin = function() {
    if (typeof this.email == 'undefined') return;
    if (this.password == '') return;
    if (this.password === null) return;
    if (typeof this.password == 'undefined') return;
    var self = this;
    if (this.emailExists) {
      $http.get('/login-check=' + this.email + '/' + this.password).success(function(data){
        if (data) {
          window.location.replace("/");
        } else {
          self.alertType = 1;
        }
      });
    } else {
      $http.post('/register', { email: this.email, password: this.password }).success(function(data){
        self.form = 3;
      });
    }
  };
  /**
   * Посылаем серверу сигнал, что надо отправить код восстановления
   */
  this.sendRestoreCode = function() {
    $http.post('/send_restore_code', { email: this.email });
  };
  this._updateLoginButton();
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
