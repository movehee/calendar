function date_change(send_date){

	let senddata = new Object();
	senddata.date = send_date;

	render('pro/special_day', senddata);
	return null;
};