$(function() {
  // Не знаю как правильно сделать так, чтобы скрытые по умолчанию элементы не моргали при загрузке страницы.
  // Поэтому использую старый топорный проверенный способ.
  setTimeout(function(){
    // Целый час потратил на поиск метода, который бы дёргался ПОСЛЕ инициализации angular, и когда сработали бы все ng-show и ng-hide
    // Поскольку я очень ограничен во времени - вот такое топорное решение
    $('.hide-on-init').removeClass('hide-on-init');
  }, 0);
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
app.controller('TopMenuController', [ '$http', '$rootScope', function($http, $rootScope){
  /**
   * Указанный пункт меню
   * 1 - Сообщения
   * 2 - Профиль
   * 3 - Админка
   * @type {number}
   */
  this.index = 0;
  this.role = $('input#role_menu').val();
  var url = new URL(document.URL);
  if (url.pathname == '/profile') {
    this.index = 2;
  } else if (url.pathname.substring(0, 6) == '/admin') {
    this.index = 3;
  } else {
    this.index = 1;
  }
  this.checkIndex = function(index) {
    return index == this.index;
  };
  var self = this;
  $rootScope.$on('changeRole', function(event, args) {
    self.role = args['role'];
  });
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
    $http.get('/oauth-register-finish').success(function(data){
      window.location.replace("/");
    });
  };
} ]);
app.controller('ProfileController', [ '$http', '$rootScope', function($http, $rootScope){
  this.profileForm = null;
  this.inputFile = null;
  this.id = $('input#id').val();
  this.email = $('input#email').val();
  this.name = $('input#name').val();
  this.vk = $('input#vk').val();
  this.google = $('input#google').val();
  this.role = $('input#role').val();
  $rootScope.$broadcast("changeRole", { role: this.role });
  this.havePassword = $('input#have_password').val() == 1;
  this.avatar = $('input#avatar').val();
  /**
   * 0 - Без уведомлений
   * 1 - Профайл успешно изменён
   * 2 - Желаемый email уже зарегистрирован в системе, что с ним будем делать?
   * 3 - Подтвердите ваш email
   * 4 - Роль модератора получена
   * 5 - Роль модератора отдана
   * 6 - Пароль успешно изменён
   * 7 - Пароль успешно задан
   * 8 - Email подтверждён
   * 9 - Email подтверждён, аккаунты соеденены
   * 10- Действующий пароль указан неверно
   * 11- Аватар удалён
   * 12- Аватар обновлён
   * @type {number}
   */
  this.alertType = 0;
  this.code = '';
  var self = this;
  setTimeout(function(){
    // К вопросу, что не разобрался где у angular onInit, поэтому завёрнуто в setTimeout
    self.dialog = $( "#dialog_change_password" ).dialog({
      autoOpen: false,
      modal: true,
      resizable: false,
      buttons: [
        {
          text: "Я передумал",
          click: function() {
            $( this ).dialog( "close" );
          }
        },
        {
          text: "Сменить",
          click: function() {
            self._setPassword();
            $( this ).dialog( "close" );
          }
        }
      ],
      open: function( event, ui ) {
        if (!self.havePassword) {
          $( this ).dialog( { title: 'Установить пароль' } );
          $('input#password').parent().hide();
          $(this).parent().find('button[class!=ui-dialog-titlebar-close]:last').html('Установить');
        } else {
          $( this ).dialog( { title: 'Сменить пароль' } );
          $('input#password').parent().show();
          $(this).parent().find('button[class!=ui-dialog-titlebar-close]:last').html('Сменить');
        }
        $(this).parent().find('button[class!=ui-dialog-titlebar-close]:first').addClass('btn btn-default');
        $(this).parent().find('button[class!=ui-dialog-titlebar-close]:last').addClass('btn btn-success');
      }
    });
  }, 0);
  this._setPassword = function() {
    $http.put('/profile-set-password', { password: $('input#password').val(), password_new: $('input#password_new').val() }).success(function(data) {
      if (data === false) {
        alert('Ошибка-нежданчик, перезайдите пожалуйста');
        return;
      }
      if (typeof data['done'] == 'undefined') {
        alert('Ошибка-нежданчик, перезайдите пожалуйста');
        return;
      }
      if (data['done']) {
        if (self.havePassword) {
          self.alertType = 6;
        } else {
          self.alertType = 7;
          self.havePassword = true;
        }
      } else {
        self.alertType = 10;
      }
    });
  };
  this.getRequestModeratorBtnTitle = function() {
    if (this.role == 'user') {
      return 'Запросить роль модератора';
    } else {
      return 'Отдать роль модератора';
    }
  };
  this.onSubmit = function() {
    this.alertType = 0;
    if ($('input#email').hasClass('ng-invalid')) return;
    var self = this;
    var file = $scope.inputFile;
    var myHeaders = {};
    if (typeof file != 'undefined') myHeaders = { headers: { 'Content-Type' : file.type } };
    $http.put('/profile?email=' + this.email + '&name=' + this.name, file, myHeaders).success(function(data){
      if (data === false) {
        alert('Ошибка-нежданчик, перезайдите пожалуйста');
        return;
      }
      if (typeof data['email_exists'] != 'undefined') {
        if (data['email_exists']) {
          self.alertType = 2;
        } else {
          self.alertType = 3;
        }
      } else if (typeof data['name'] != 'undefined') {
        self.alertType = 1;
      } else {
        alert('Ошибка-нежданчик, перезайдите пожалуйста');
      }
      if (typeof data['avatar'] != 'undefined') {
        self.avatar = data['avatar'];
        var image = $("#image");
        image.wrap('<form>').parent('form').trigger('reset');
        image.unwrap();
      }
    });
  };
  this.onChangePassword = function() {
    this.dialog.dialog('open');
  };
  this.onRequestModerator = function() {
    var self = this;
    $http.put('/profile-request-moderator').success(function(data){
      if (data === false) {
        alert('Ошибка-нежданчик, перезайдите пожалуйста');
        return;
      }
      if (typeof data['got'] == 'undefined') {
        alert('Ошибка-нежданчик, перезайдите пожалуйста');
        return;
      }
      if (data['got']) {
        self.alertType = 4;
        self.role = 'moderator';
      } else {
        self.alertType = 5;
        self.role = 'user';
      }
      $rootScope.$broadcast("changeRole", { role: self.role });
    });
  };
  this.onSocial = function(social) {
    if (this[social] != '') return;
    $http.get('/profile-social-get-url=' + social).success(function(data){
      if (data === false) {
        alert('Ошибка-нежданчик, перезайдите пожалуйста');
        return;
      }
      window.location.href = data;
      // TODO: вывести какое-то уведомление типа "всё прошло хорошо" или "что-то пошло не так"
    });
  };
  this.onConfirm = function() {
    var self = this;
    $http.put('/profile-confirm', { code: this.code }).success(function(data){
      if (data === false) {
        alert('Ошибка-нежданчик, перезайдите пожалуйста');
        return;
      }
      if (typeof data['check'] == 'undefined') {
        alert('Ошибка-нежданчик, перезайдите пожалуйста');
        return;
      }
      if (data['check'] === false) return;
      if (self.alertType == 2) {
        self.alertType = 9;
      } else {
        self.alertType = 8;
      }
    });
  };
  this.onAvatarRemove = function() {
    var self = this;
    $http.delete('/profile-avatar-remove').success(function(data){
      if (data !== true) {
        alert('Ошибка-нежданчик, перезайдите пожалуйста');
        return;
      }
      self.alertType = 11;
      self.avatar = '';
    });
  };
} ]);
app.controller('PostController', [ '$http', function($http){
  this.mode = 1;
  this.id = $('input#id').val();
  this.title = $('input#title').val();
  this.content = $('input#message').val();
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
    // Отправка серверу выполняется в 2 шага - сначала все inline картинки отправляются, потом тело сообщения TODO: implement this
    // Внешние картинки закачивать себе не будем.
    /**
     * Важно знать, что в конце контроллера дёргается метод "_updatePosts".
     */
    if (this.title == '') return;
    var self = this;
    $http.post('/post', { title: this.title, content: this.content }).success(function(data){
      if (data !== false) {
        self.mode = 1;
        self.alertType = 1;
        data.time *= 1000;
        self.posts.push(data);
      } else {
        alert('Ошибка-нежданчик, перезайдите пожалуйста');
      }
    });
  };
  this.onEditSubmit = function() {
    // Примерно такая же логика как и onNewSubmit, только обновляем данные
    // TODO: отправить inline картинки
    if (this.title == '') return;
    var self = this;
    $http.put('/post=' + this.id, { title: this.title, content: this.content }).success(function(data){
      if (data !== false) {
        // Всё хорошо - редирект на главную страницу
        // TODO: нарисовать что-то типа "успешно отредактировано"
        window.location.href = '/';
      } else {
        alert('Ошибка-нежданчик, перезайдите пожалуйста');
      }
    });
  };
  this.onDeleteSubmit = function() {
    // Примерно такая же логика как и onNewSubmit, только обновляем данные
    if (this.title == '') return;
    var self = this;
    $http.delete('/post=' + this.id).success(function(data){
      if (data !== false) {
        // Всё хорошо - редирект на главную страницу
        // TODO: нарисовать что-то типа "успешно удалено"
        window.location.href = '/';
      } else {
        alert('Ошибка-нежданчик, перезайдите пожалуйста');
      }
    });
  };
  this.updatePosts = function() {
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
} ]);
app.controller('AdminPostController', [ '$http', function($http){
  // Наверное было бы правильно объеденить код с PostController
  // Но в виду очень сжатых сроков - нет времени думать как правильно это сделать
  //
  // Ещё я бы сделал на стороне JS классы для объектов пользователей и сообщений
  // Но опять таки - безумные сроки
  this.id = $('input#id').val();
  this.title = $('input#title').val();
  this.content = $('input#message').val();
  this.posts = [];
  this.users = [];
  this.updatePosts = function() {
    var self = this;
    $http.get('/admin/posts-list').success(function(data){
      if (data !== false) {
        self.users = [];
        for (var index in data['users']) {
          var user = data['users'][index];
          self.users[user['id']] = user;
        }
        self.posts = [];
        for (var index in data['posts']) {
          var post = data['posts'][index];
          post.time *= 1000;
          post.authorName = self.users[post.id]['name']; // Мне такой способ использования очень не нравится, но времени изучать как правильно это сделать - нет
          self.posts.push(post);
        }
      } else {
        alert('Ошибка-нежданчик, перезайдите пожалуйста');
      }
    });
  };
  this.onEditSubmit = function() {
    if (this.title == '') return;
    var self = this;
    $http.put('/admin/post=' + this.id, { title: this.title, content: this.content }).success(function(data){
      if (data !== false) {
        // TODO: нарисовать что-то типа "успешно отредактировано"
        window.location.href = '/admin/posts';
      } else {
        alert('Ошибка-нежданчик, перезайдите пожалуйста');
      }
    });
  };
  this.onDeleteSubmit = function() {
    if (this.title == '') return;
    var self = this;
    $http.delete('/admin/post=' + this.id).success(function(data){
      if (data !== false) {
        // TODO: нарисовать что-то типа "успешно удалено"
        window.location.href = '/admin/posts';
      } else {
        alert('Ошибка-нежданчик, перезайдите пожалуйста');
      }
    });
  };
} ]);
app.controller('AdminUserController', [ '$http', function($http){
  this.id = null;
  this.users = [];
  this.updateUsers = function() {
    var self = this;
    $http.get('/admin/users-list').success(function(data){
      if (data !== false) {
        self.users = [];
        for (var index in data['users']) {
          var user = data['users'][index];
          self.users[user['id']] = user;
        }
      } else {
        alert('Ошибка-нежданчик, перезайдите пожалуйста');
      }
    });
  };
  this.onEditSubmit = function() {
    var self = this;
    $http.put('/admin/user=' + this.id, { name: this.name }).success(function(data){
      if (data !== false) {
        // TODO: нарисовать что-то типа "успешно отредактировано"
        window.location.href = '/admin/users';
      } else {
        alert('Ошибка-нежданчик, перезайдите пожалуйста');
      }
    });
  };
  this.onDeleteSubmit = function() {
    if (this.title == '') return;
    var self = this;
    $http.delete('/admin/user=' + this.id).success(function(data){
      if (data !== false) {
        // TODO: нарисовать что-то типа "успешно удалено"
        window.location.href = '/admin/users';
      } else {
        alert('Ошибка-нежданчик, перезайдите пожалуйста');
      }
    });
  };
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
      CKEDITOR.allowedContent = true;
      var config = {
        customConfig : '',
        toolbarCanCollapse : false,
        colorButton_enableMore : false,
        width: 800,
        simpleImageBase64allowed: true,
        toolbar :
          [
            { name: 'document',    items : [ 'Source' ] },
            { name: 'clipboard',   items : [ 'Cut','Copy','Paste','PasteText','PasteFromWord','-','Undo','Redo' ] },
            { name: 'basicstyles', items : [ 'Bold','Italic','Underline','Strike','Subscript','Superscript','-','RemoveFormat' ] },
            { name: 'paragraph',   items : [ 'CreateDiv','-','JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock'] },
            { name: 'colors',      items : [ 'TextColor','BGColor' ] },
            { name: 'insert',      items : [ 'Link', 'Image', 'addFile', 'addImage' ] },
            { name: 'tools',       items : [ 'Maximize', 'About' ] }
          ],
        simpleuploads_acceptedExtensions :'7z|avi|csv|doc|docx|flv|gif|gz|gzip|jpeg|jpg|mov|mp3|mp4|mpc|mpeg|mpg|ods|odt|pdf|png|ppt|pxd|rar|rtf|tar|tgz|txt|vsd|wav|wma|wmv|xls|xml|zip'
      };
      var ck = CKEDITOR.replace(elm[0], config);

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
app.directive('file', function() {
  return {
    restrict: 'E',
    template: '<input type="file" />',
    replace: true,
    require: 'ngModel',
    link: function(scope, element, attr, ctrl) {
      var listener = function() {
        scope.$apply(function() {
          attr.multiple ? ctrl.$setViewValue(element[0].files) : ctrl.$setViewValue(element[0].files[0]);
        });
      }
      element.bind('change', listener);
    }
  }
});