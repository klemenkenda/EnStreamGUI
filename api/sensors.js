SensorAPI = {}

SensorAPI.getMeasurements = function(conf, callback){
  url = 'http://sensors.ijs.si/xml/get-measurements/' + conf.par1 + ':' + conf.par2 + ':' + conf.par3 + ':' + conf.par4
  $.get(url, function(data){
    callback(data)
  })
}