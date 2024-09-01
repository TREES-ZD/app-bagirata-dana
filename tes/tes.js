/*
 * Basic responsive mashup template
 * @owner Enter you name here (xxx)
 */
/*
 *    Fill in host and port for Qlik engine
 */
var prefix = window.location.pathname.substr( 0, window.location.pathname.toLowerCase().lastIndexOf( "/extensions" ) + 1 );
var config = {
	host: window.location.hostname,
	prefix: prefix,
	port: window.location.port,
	isSecure: window.location.protocol === "https:"
};
require.config( {
	baseUrl: ( config.isSecure ? "https://" : "http://" ) + config.host + (config.port ? ":" + config.port : "") + config.prefix + "resources"
} );

require( ["js/qlik"], function ( qlik ) {
	qlik.setOnError( function ( error ) {
		$( '#popupText' ).append( error.message + "<br>" );
		$( '#popup' ).fadeIn( 1000 );
	} );
	$( "#closePopup" ).click( function () {
		$( '#popup' ).hide();
	} );

	//callbacks -- inserted here --
	//open apps -- inserted here --
	var app = qlik.openApp('2555b9ba-f558-4528-9a17-7a24ddbd9887', config);

	//get objects -- inserted here --
	app.getObject('QV01','PgutRG');
	//create cubes and lists -- inserted here --

app.visualization.create(
  'linechart',
  [
    {
      "qDef": {
        "qGrouping": "N",
        "qFieldDefs": [
          "=month(OrderDate)"
        ],
        "qFieldLabels": [
          "Date"
        ]
      }
    },
    {
      "qDef": {
        "qLabel": "Pts",
        "qGrouping": "N",
        "qDef": "Sum(Sales)",
        "qNumFormat": {
          "qType": "U",
          "qnDec": 10,
          "qUseThou": 0
        }
      },
	  "color": {
  "auto": false,
  "mode": "byMeasure",
  "measureScheme": "dc"
}
    }
  ],
  {
    "showTitles": true,
    "title": "Stableford point trend",
	"lineType": "area",
	"nullMode": "connect",
	"dataPoint": {
    "show": true,
    "showLabels": false
	},
    "gridLine": {
      "auto": false,
      "spacing": 3
    }
  }
).then(function(vis){
  vis.show("QV01");
});

} );
