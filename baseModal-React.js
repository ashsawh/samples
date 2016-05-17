var MenuItem = function(name, type, url) {
  this.name = name;
  this.type = type;
  this.url = url;
}

var BASE_URL = '###';
var initDesktopLoadData = [
  new MenuItem('Mail', 'snail', BASE_URL + '/wp-content/uploads/2015/04/mail-icon.png'),
  new MenuItem('Email', 'email', BASE_URL + '/wp-content/uploads/2015/04/email-icon.png'),
  new MenuItem('Text', 'text', BASE_URL + '/wp-content/uploads/2015/04/sms-icon.png'),
  new MenuItem('Print', 'print', BASE_URL + '/wp-content/uploads/2015/04/print-icon.png')
];

var Icon = React.createClass({
  render: function() {
    var classes = this.props.type + 'GroupLink';
    return (
      <a href="javascript:;" className={classes}>
        <img src={this.props.url} className="imgModal" />
      </a>
    )
  }
})

var Row = React.createClass({
  render: function() {
    return <div className='row'>{ this.props.children }</div>
  }
})

var Column = React.createClass({
  render: function() {
    return <div className={this.props.classes}>{ this.props.children }</div>
  }
})

var ModalButton = React.createClass({
  render: function() {
    var suffixes = ['GroupLink', 'Button'];
    var id = this.props.type + 'Button';
    var classes = ['modalButtons'];

    suffixes.forEach(function(element) {
      classes.push(this.props.type + element);
    }, this);
    var classNames = classes.join(' ');
    return (
      <button className={classNames} id={id}>{this.props.name}</button>
    )
  }
})

var Body = React.createClass({
  render: function() {
    var buttonRows = [];
    var iconRows = [];
    this.props.data.forEach(function(MenuItem) {
      var buttonKey = MenuItem.name + 'Button';
      var iconKey = MenuItem.name + 'Icon';
      buttonRows.push(
        <Column classes="col-mid-4"  key={buttonKey}>
          <Icon type={MenuItem.type} url={MenuItem.url} />
        </Column>
      );
      iconRows.push(
        <Column classes="col-mid-4" key={iconKey}>
          <ModalButton type={MenuItem.type} name={MenuItem.name} />
        </Column>
      );
    });
    return (
      <div>
        <Row>{buttonRows}</Row>
        <Row>{iconRows}</Row>
      </div>
    )
  }
});

var Panel = React.createClass({
  var title = this.props.title;
  var desc = this.props.desc;
  var footer = this.props.footer;
});

var DefaultDisclaimerMessage = React.createClass({
    render: function() {
      return (
        <Disclaimer>
          <span>
            By signing up, you agree to our <a href="' + BASE_URL + '/privacy-policy/">
            Privacy Policy</a> and <a href="' + BASE_URL + 'terms-of-service/">Terms of Service</a>
          </span>
        </Disclaimer>
      )
    }
})

var Disclaimer = React.createClass({
  render: function() {
    var wrapperClass = this.props.classes ? this.props.classes : 'rpDisclaimer';
    return (
      <div className={wrapperClass}>
        {this.props.children}
      </div>
    )
  }
}) 


ReactDOM.render(
  <Body data={ initDesktopLoadData } />,
  document.getElementById('container')
);