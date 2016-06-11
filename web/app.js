$(function() {
  // Не знаю как правильно сделать так, чтобы скрытые по умолчанию элементы не моргали при загрузке страницы.
  // Поэтому использую старый топорный проверенный способ.
  $('.form-signin').css('display', 'block');
  $('.hide-on-init').css('display', 'block');
});

// Я и потратил и так кучу времени на конвертацию времени туда-сюда
// Поэтому использую может и не очень красивое, но рабочее решение этого вопроса
function twoDigits(d) {
  if(0 <= d && d < 10) return "0" + d.toString();
  if(-10 < d && d < 0) return "-0" + (-1*d).toString();
  return d.toString();
}
Date.prototype.toMysqlFormat = function() {
  return this.getUTCFullYear() + "-" + twoDigits(1 + this.getUTCMonth()) + "-" + twoDigits(this.getUTCDate()) + " " + twoDigits(this.getUTCHours()) + ":" + twoDigits(this.getUTCMinutes()) + ":" + twoDigits(this.getUTCSeconds());
};

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
  this.mode = 1;
  this.title = '';
  this.content = '';
  /**
   * 0 - Без уведомлений
   * 1 - Сообщение успешно добавлено
   * @type {number}
   */
  this.alertType = 0;
  this.posts = [];
  this.onNew = function() {
    this.mode = 2;
    this.alertType = 0;
  };
  this.onNewCancel = function() {
    this.mode = 1;
  };
  this.onNewSubmit = function() {
    // Тут мы должны проверить только title - не пустой ли он (дублирование заголовков проверять не будем)
    // Если есть ошибка - вывести её.
    // Если нет ошибки - отправить серверу, и если он примет - добавить в список сообщений это сообщение
    // Отправка серверу выполняется в 2 шага - сначала все inline картинки отправляются, потом тело сообщения
    // Внешние картинки закачивать себе не будем.
    /**
     * Важно знать, что в конце контроллера дёргается метод "_updatePosts".
     */
    if (this.title == '') return;
    var self = this;
    $http.post('/post', { title: this.title, content: this.content }).success(function(data){
      if (data) {
        self.mode = 1;
        self.alertType = 1;
        var currentdate = new Date();
        self.posts.push({
          title: self.title,
          text: self.content,
          time: currentdate.toMysqlFormat(),
          author: 0
        });
        // TODO: получать time и author из ответа сервера
      } else {
        alert('Ошибка-нежданчик, перезайдите пожалуйста');
      }
    });
  };
  this._updatePosts = function() {
    var self = this;
    $http.get('/posts').success(function(data){
      if (data !== false) {
        self.posts = [];
        for (var index in data) {
          var post = data[index];
          post.time *= 1000;
          self.posts.push(post);
        }
      } else {
        alert('Ошибка-нежданчик, перезайдите пожалуйста');
      }
    });
  };

  this._updatePosts();
} ]);
app.filter('orderObjectBy', function() {
  return function (items, field, reverse) {
    var filtered = [];
    angular.forEach(items, function(item) {
      filtered.push(item);
    });
    filtered.sort(function (a, b) {
      return (a[field] > b[field] ? 1 : -1);
    });
    if(reverse) filtered.reverse();
    return filtered;
  }
});
app.directive('ckEditor', function() {
  return {
    require: '?ngModel',
    link: function(scope, elm, attr, ngModel) {
      var ck = CKEDITOR.replace(elm[0], { width: 800 });

      if (!ngModel) return;

      ck.on('instanceReady', function() {
        ck.setData(ngModel.$viewValue);
      });

      function updateModel() {
        scope.$apply(function() {
          ngModel.$setViewValue(ck.getData());
        });
      }

      ck.on('change', updateModel);
      ck.on('key', updateModel);
      ck.on('dataReady', updateModel);

      ngModel.$render = function(value) {
        ck.setData(ngModel.$viewValue);
      };
    }
  };
});
app.filter('rawHtml', ['$sce', function($sce){
  return function(val) {
    return $sce.trustAsHtml(val);
  };
}]);
