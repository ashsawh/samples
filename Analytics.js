// Analytics
var Analytics = function(marker) {
  this.marker = typeof marker === 'undefined' ? '#modalSec' : marker;
  //this.isFlow = window.location.href.indexOf('flow') > 1 ? true : false;
  this.isFlow = false;
  var floodLight = function(type) {
    this.type = type;
    this.iframe = '';
    this.url = '';
    this.axel = function() {
      var randVar = Math.random() + "";
      return randVar * 10000000000000;
    }
    this.floodLightActions = {
            'Address': 'reb101',
            'Email': 'reb202',
            'Print': 'reb201',
            'SMS': 'reb203',
            'EmailCoupon': 'reb302',
            'SMSCard': 'rebat00'
    }
    this.make = function() {
      if (this.floodLightActions.hasOwnProperty(this.type)) {
        var actionCode = this.floodLightActions[this.type];
        var axelRand = this.axel();
        this.url =
          "https://###.fls.doubleclick.net/activityi;src=###;type=reb;cat=" +
          actionCode + ";ord=" + axelRand + "?";
        console.log('Flood Light URL: ' + this.url);
        this.iframe = $("<iframe src='" + this.url +
          "' width='1' height='1' frameborder='0' style='display:none'></iframe>"
        );
        return this.iframe;
      }
    }
    this.get = function() {
      return this.iframe;
    }
  }
  var adWords = function(type) {
    this.type = type;
    this.url = '';
    this.div = '';
    this.adWordActions = {
            'SMS': '###',
            'Address': '###',
            'Email': '###',
            'EmailCoupon': '###',
            'SMSCoupon': '###'
    }
    this.make = function() {
      if (this.adWordActions.hasOwnProperty(this.type)) {
        var actionCode = this.adWordActions[this.type];
        this.url =
          "//www.googleadservices.com/pagead/conversion/974982826/?label=" +
          actionCode + ";guid=ON&amp;script=0";
        console.log('Ad words URL: ' + this.url);
        this.div = $(
          '<div style="display:inline;"><img height="1" width="1" style="border-style:none;" alt="" src="' +
          this.url + '"/></div>');
        return this.div;
      }
    }
    this.get = function() {
      return this.div;
    }
  }

   var Marin = function(type) {
      this.type = type;
      this.url = '';
      this.div = '';
      this.marinActions = {
          'SMSCoupon': 'smscoupon',
          'SMS': 'smscard',
          'Address': 'addresscard',
          'Email': 'emailcard',
          'EmailCoupon': 'emailcoupon',
          'Print': 'cardprint'
      };
      this.make = function() {
        console.log(this.type + " Marin has been fired (" + this.marinActions[this.type] + ")");
        var _mTrack = _mTrack || [];
        var randOrder = Math.random() * 1000000;
        _mTrack.push(['addTrans', {
            currency :'USD',
            items : [{
                orderId : randOrder,
                convType : this.marinActions[this.type],
            }]
        }]);
        _mTrack.push(['processOrders']);
        window._mTrack = _mTrack;
        marinCaller();
      };
      this.get = function() {
          return this.div;
      };
      this.getPixel = function() {
	console.log("Analytics type is: " + this.type);
	console.log(this.type + " Marin has been fired (" + this.marinActions[this.type] + ")");
        var randNum = Math.random() * 1000000;
        return $('<img width="1" height="1" src="https://tracker.marinsm.com/tp?act=2&cid=###&trans=UTM:I||' + this.marinActions[type] + '||||&rnd=' + randNum + '" />');
      }
  }

  var googleAnalytics = function(type) {
    this.type = type;
    this.isFlow = window.location.href.indexOf('flow') > 1 ? true : false;
    this.isFlow = false;
    this.gaLookUp = {
      'Address': ['mailCard', 'mailReq'],
      'EmailCoupon': ['email', 'emailCoupon'],
      'Email': ['emailCard', 'emailCardReq'],
      'Print': ['print', 'printing'],
      'SMS': ['SMS', 'smsCoupon'],
      'MobileCoupon': ['mobile', 'mobileCouponReq'],
      'MobileCard': ['mobile', 'mobileView'],
      'Card': ['pharmaCard', 'pharmaReq'],
      'News': ['newsletter', 'newsReq'],
      'Overall': ['overall', 'overallCoupon'],
      'Address': ['mailCard', 'mailReq'],
      'EmailFlow': ['emailFlow', 'flowEmailCoupon'],
      'PrintFlow': ['printFlow', 'flowPrinting'],
      'SMSFlow': ['SMSFlow', 'smsFlowCoupon'],
      'NewsFlow': ['newsFlow', 'newsFlowReq'],
      'Dupe': ['repeatAddress', 'repeatAddyReq']
    };

    this.fire = function(overall) {
      if (this.gaLookUp.hasOwnProperty(this.type)) {
        if (typeof location !== 'undefined') overall = true;
        var tagData = this.gaLookUp[this.type];
        console.log('GA name is ' + tagData[0] + ' and funcname is ' +
          tagData[1]);
        ga('send', 'event', tagData[0], tagData[1]);
        console.log(this.isFlow);
        if (this.isFlow) {
          var flowLookUp = this.type + 'Flow';
          var flowTagData = this.gaLookUp[flowLookUp];
          ga('send', 'event', flowTagData[0], flowTagData[1]);
          console.log('GA name is ' + flowTagData[0] +
            ' and funcname is ' + flowTagData[1]);
        }
        if (overall) ga('send', 'event', 'overall', 'overallCoupon');
      }
    }
  }
  this.attach = function(type, paidCheck, fireGA, location) {
    location = typeof location === 'undefined' ? this.marker : location;
    if (paidCheck && isUserFromPaid()) {
      $(location).append((new adWords(type)).make());
      $(location).append((new floodLight(type)).make());
      $(location).append((new Marin(type)).getPixel());
    }
    if (fireGA)(new googleAnalytics(type)).fire();
  }
  this.fireGoogleAnalytics = function(type) {
    (new googleAnalytics(type)).fire();
  }
}