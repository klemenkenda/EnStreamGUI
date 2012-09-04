function convertUnitHTML(data) {
  data = data.replace("^2", "<sup>2</sup>");
	data = data.replace("^3", "<sup>3</sup>");
	
	return data;
}