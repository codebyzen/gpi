function humanSize(bytes, decimals = 2) {
	if (typeof bytes == 'undefined') return '';
	var sz = ['B','Kb','Mb','Gb','Tb','Pb'];
	var factor = Math.floor((bytes.toString().length - 1) / 3);
	return (bytes / Math.pow(1024, factor)).toFixed(decimals) +' '+ sz[factor];
}