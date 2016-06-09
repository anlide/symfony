$(function() {
  // Не знаю как правильно сделать так, чтобы скрытые по умолчанию элементы не моргали при загрузке страницы.
  // Поэтому использую старый топорный проверенный способ.
  $('.form-signin').css('display', 'block');
});

var app = angular.module('symfonyCult', []);
app.config(function($interpolateProvider) {
  $interpolateProvider.startSymbol('[[');
  $interpolateProvider.endSymbol(']]');
});
app.controller('TopMenuController', [ '$http', function($http){
  /**
   * Указанный пункт меню
   * 1 - Сообщения
   * 2 - Профиль
   * @type {number}
   */
  this.index = 0;
  var url = new URL(document.URL);
  if (url.pathname == '/profile') {
    this.index = 2;
  } else if (url.pathname.substring(0, 6) == '/admin') {
    this.index = 3;
  } else {
    this.index = 1;
  }
} ]);
app.controller('AuthController', [ '$http', function($http){
  this.email = '';
  this.password = '';
  this.codeRestore = '';
  this.codeRegister = '';
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
   * null - Ещё не спрашивали сервер
   * true - при последнем запросе указанный email подтверждён
   * false - при последнем запросе указанный email не подтверждён
   * @type {null|boolean}
   */
  this.emailConfirmed = null;
  /**
   * 0 - Без уведомлений
   * 1 - Неверный пароль при попытке логина
   * 2 - Код регистрации не введён
   * 3 - Пароля нет (когда был вход только через соц сеть)
   * @type {number}
   */
  this.alertType = 0;
  /**
   * 0 - Без уведомлений
   * 1 - Неверный код
   * @type {number}
   */
  this.alertRestoreType = 0;
  /**
   * 0 - Без уведомлений
   * 1 - Неверный код
   * @type {number}
   */
  this.alertRegisterType = 0;
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
      self.emailExists = data['exists'];
      self.emailConfirmed = data['confirmed'];
      self._updateLoginButton();
    });
  };
  this.onChangePassword = function() {
    this._updateLoginButton();
  };
  this._updateLoginButton = function() {
    if ((this.email == '') || (typeof this.email == 'undefined')) {
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
        self.emailConfirmed = data['confirmed'];
        if (!self.emailConfirmed) {
          self.alertType = 2;
          return;
        }
        if (!data['password_exists']) {
          self.alertType = 3;
          return;
        }
        if (data['valid']) {
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
  this.switchForm = function(newForm) {
    switch (newForm) {
      case 1:
        this.alertType = 0;
        break;
      case 2:
        this.alertRestoreType = 0;
        this.sendRestoreCode();
        break;
      case 3:
        this.alertRegisterType = 0;
        this.sendRegisterCode();
        break;
      default:
        this.alertType = 0;
        newForm = 1;
    }
    this.form = newForm;
  };
  /**
   * Посылаем серверу сигнал, что надо отправить код восстановления/регистрации
   */
  this.sendRestoreCode = function() {
    $http.post('/send_restore_code', { email: this.email });
  };
  this.sendRegisterCode = function() {
    $http.post('/send_register_code', { email: this.email });
  };
  this.onChangeRestoreCode = function() {
    if (this.codeRestore.length != 7) return;
    $http.get('/code-check=' + this.email + '/' + this.codeRestore).success(function(data){
      if (data) {
        window.location.replace("/");
      } else {
        self.alertRestoreType = 1;
      }
    });
  };
  this.onChangeRegisterCode = function() {
    if (this.codeRegister.length != 7) return;
    $http.get('/code-check=' + this.email + '/' + this.codeRegister).success(function(data){
      if (data) {
        window.location.replace("/");
      } else {
        self.alertRegisterType = 1;
      }
    });
  };
  this._updateLoginButton();
} ]);
app.controller('UserController', [ '$http', function($http){
} ]);
app.controller('OauthController', [ '$http', function($http){
  this.onRegister = function() {
    $http.get('/oauth/register-finish').success(function(data){
      window.location.replace("/");
    });
  };
} ]);
app.controller('ProfileController', [ '$http', function($http){
} ]);
app.controller('PostController', [ '$http', function($http){
} ]);
