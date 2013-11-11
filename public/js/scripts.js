/*global $, console, Test, window, document */

$(function () {
	"use strict";

	var Test = window.Test = window.Test || {};

	Test.NSNC = (function () {
		var TestNSNC = function () {};
		TestNSNC.prototype = {
			init: function () {
				console.log('JS init!');
			}
		};
		return new TestNSNC();
	}());

	Test.init = function () {
		this.NSNC.init();
	};
	Test.init();
});