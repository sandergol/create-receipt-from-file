const HandlerForm = {
  ajaxURL: 'ajax.php', state: {
    form: document.querySelector('.container-form form')
  },

  init() {
    this.formSubmit();
  },

  formSubmit() {
    let _this = this;

    this.state.form.onsubmit = (event) => {
      event.preventDefault();

      let formData = new FormData(this.state.form);

      _this.sendRequest(formData);
    };
  },

  sendRequest(formData) {
    let xhr = new XMLHttpRequest();
    xhr.open('POST', this.ajaxURL);
    xhr.send(formData);

    xhr.onload = () => this.resultRequest(xhr);
  },

  resultRequest(xhr) {
    let message = '';

    try {
      message = decodeURIComponent(JSON.parse(xhr.responseText)['message']);
    } catch (e) {
      message = decodeURIComponent(xhr.responseText);
    }

    let div = document.createElement('div');
    div.className = 'container-result';

    console.log('xhr', xhr);

    switch (xhr.status) {
      case 200:
        try {
          let url = new URL(JSON.parse(xhr.responseText)['message']);
          div.innerHTML = '<p>Ссылка на оплату: <a href="' + url.href + '" target="_blank">' + url.href + '</a></p>';
        } catch (e) {
          div.innerHTML = message;
        }

        break;
      default:
        div.innerHTML = '<p>Произошла ошибка: ' + xhr.status + '</p><p>Получен ответ: ' + message + '</p>';
    }

    this.state.form.closest('.container-form').after(div);
    this.state.form.reset();
  }
};

HandlerForm.init();
