var progressBar = function() {
  var progressBarItem = function(label, className, title, isFirst) {
    this.bar = $('<span class="bar"></span>');
    this.circle = $('<div class="circle ' + className + '"></div>');
    this.label = $('<span class="label">' + label + '</span>');
    this.title = $('<span class="title">' + title + '</span>');
    this.isFirst = isFirst;
    this.make = function() {
      this.circle.append(this.label);
      this.circle.append(this.title);
      var empty = ($('<div>').append(this.bar)).append(this.circle);
      return isFirst ? this.circle : empty.contents();
    }
  }
  this.progress = $('<div class="progress"></div>');
  this.bar = [
    (new progressBarItem(1, 'firstName', 'First', 1)).make(), (new progressBarItem(
      2, 'lastName', 'Last', 0)).make(), (new progressBarItem(3,
      'streetAddr', 'Street', 0)).make(), (new progressBarItem(4, 'zipCode',
      'Zipcode', 0)).make()
  ];
  this.instantiate = function() {
    this.progress.append(this.bar);
    $('#modalSec').append(this.progress);
  }
  this.makeActive = function(className) {
    $('div.progress div.circle').removeClass('active');
    $('div.progress div.' + className).addClass('active');
  }
  this.complete = function(className) {
    $('div.progress div.' + className).removeClass('active');
    $('div.progress div.' + className).addClass('done');
  }
}

var screenStatus = function(submitListener) {

  this.submitListener = submitListener;
  this.screen = 'initial';
  this.isChannel = getQueryVariable('channel');
  this.screens = {
    'SMS': 'SMSFormInput',
    'Email': 'emailFormInput',
    'Mail': 'addressColInput',
    'News': 'newsEmail'
  }
  this.set = function(name) {
    //alert(name);
    this.screen = name;
  }
  this.getInput = function() {
    if (this.screens.hasOwnProperty(screen)) return this.screens[screen]
  }
  this.get = function() {
    return this.screen;
  }
  this.callListener = function(e) {
    switch (this.screen) {
      case 'text':
        return this.submitListener.text(true);
      case 'email':
        return this.submitListener.email(true);
      case 'newsletter':
        return this.submitListener.newsletter();
      case 'address':
        return this.submitListener.address(e, true);
    }
  }
  this.isRowButton = function(e) {
    if($('.desktopRow').length !== 0 && !this.isChannel) {
        
      return this.callListener(e); 
 
    }
  }
  this.isHomeButton = function(e) {
    if ($('desktopRow').length !== 0) {
      this.callListener(e);
    }
  }
}

// Control Objects -- Loads Interface
var bottomRowButton = function(name, className, id) {
  this.getColumn = function(className) {
    if (typeof(className) === 'undefined') className = 'buttonRowColumn';
    return $('<div class="' + className + ' desktopRowCol"></div>');
  };
  //this.button = $('<button class="' + id + ' modalButtons ' + className + '" id="' + id + '">' + name + '</button>');
  this.button = $('<button class="' + id + '">' + name + '</button>');
  this.make = function() {
    var col = this.getColumn();
    return col.append(this.button);
  }
}

var bottomRow = function(option) {

  this.row = $(
    '<div class="row desktopRow" style="margin: 10px 0px 0px 10px !important;"></div>'
  );
  this.options = {
    'SMS': 'text',
    'Print': 'print',
    'Email': 'email',
    'Mail': 'snail'
  }; 

  this.getButton = function(buttonData) {
    console.log(buttonData);
    var buttomRowButton = new bottomRowButton(
      buttonData.name,
      buttonData.buttonName,
      buttonData.group
    );
    return this.row.append(buttomRowButton.make());
  }

  this.make = function(row) {
    var that = this;
    $.each(row, function(index, value) {
      button = that.getButton(value);
      that.row.append(button);
    });
    return this.row;
  }
}

var buttonData = function(name, prefix) {
  this.name = name;
  this.buttonName = prefix + 'Button';
  this.group = prefix + 'GroupLink';
}

var mobileModalButton = function(name, url, desc, wrapName) {
  this.icon = $("<img src='" + url + "'>");
  this.iconWrapper = $("<div class='mobileDeliveryIcon'></div>");
  this.name = $("<div class='mobileDeliveryOption'>" + name + "</div>");
  this.desc = $("<div class='mobileDeliveryExp'>" + desc + "</div>");
  this.get = function() {
    this.iconWrapper.append(this.icon);
    var link = $("<a href='javascript:;' class='mobileGroupLink " +
      wrapName + "'></a>");
    var panel = $("<div class='mobileDelivery'></div>");
    panel.append(this.iconWrapper);
    panel.append(this.name);
    panel.append(this.desc);
    link.append(panel);
    return link;
  };
};

var mobileModal = function() {
  this.coupon = new mobileModalButton('Coupon',
    BASE_URL + 'wp-content/uploads/2015/04/print-icon.png',
    'Show your pharmacist', 'cardGroupLink');
  this.SMS = new mobileModalButton('Text',
    BASE_URL + 'wp-content/uploads/2015/04/sms-icon.png',
    'Get your coupon by SMS', 'textGroupLink');
  this.email = new mobileModalButton('Email',
    BASE_URL + 'wp-content/uploads/2015/04/email-icon.png',
    'Get your coupon by Email', 'emailGroupLink');
  this.mail = new mobileModalButton('Mail',
    BASE_URL + 'wp-content/uploads/2015/04/mail-icon.png',
    'Get our card in the mail', 'snailGroupLink');
  this.row = $("<div class='row mobileRow'></div>");
  this.get = function() {
    this.row.append(this.SMS.get());
    this.row.append(this.coupon.get());
    this.row.append(this.mail.get());
    this.row.append(this.email.get());
    return this.row[0].outerHTML;
  }
}