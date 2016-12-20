'use strict';

import Backbone from 'backbone';
import Marionette from 'backbone.marionette';
import _ from 'underscore';
import App from '../App';

export default Marionette.View.extend({
	
	itemViewContainer: '#status',

	tplNotFound	: _.template('Not found'),
	tplLoad     : _.template('<%= page * 10 %> / <%= total %>'),
	tplFinish   : _.template('<%= total %> / <%= total %>'),

	initialize: function() {
		this.triggerMethod('reset');
		this.on('getEvents', this.getEvents);
		this.on('index-route', this.off);
		this.listenTo(this.model, 'change', this.render);
	},

	render: function() {
		if (this.model.get('page') < this.model.get('totalPages')) {
			this.$el.html(this.tplLoad(this.model.toJSON()));
		} else {
			this.$el.html(this.tplFinish(this.model.toJSON()));
		}

		if (!this.model.get('totalPages')) {
			this.$el.html(this.tplNotFound());
		}
	},

	off: function() {
		this.reset();
		this.$el.hide();
	},

	reset: function() {
		this.triggerMethod('resetCluster');
		this.model.set(this.model.defaults);
	},

	search: function(param) {
		this.triggerMethod('setParam', param);
		this.$el.show();
		this.triggerMethod('showControls');
	},

	getEvents: function(search) {
		this.reset();
		this.search(search.param);

		var self = this;

		fetchEvents(search);
	},

	fetchEvents: function(search) {
		Backbone.ajax({
			url: 'http://ws.audioscrobbler.com/2.0/',
			type: 'GET',
			data: {
				method        : search.param + '.getevents',
				location      : search.value,
				artist        : search.value,
				autocorrect   : 1,
				festivalsonly : search.fest,
				tag           : search.tag,
				page 		  : self.model.get('page'),
				limit	      : 10,
				api_key  	  : 'dd349d2176d3b97b8162bb0c0e583b1c',
				format 		  : 'json'
			},
			success: function(data) {
				self.getEventsData(data, search.param);

				self.model.set('page', self.model.get('page') + 1);

				if (self.model.get('page') <= self.model.get('totalPages')) {
					fetchEvents(search);
				} else {
					this.triggerMethod('addPaths');
				}
			}
		});
	},

	getEventsData: function(data, param) {
		var self = this;

		if (data.error == 8 || data.events.total == 0) {
			this.model.set({totalPages: 0});
			return false;
		}

		this.model.set({
			totalPages : data.events["@attr"].totalPages,
			total      : data.events["@attr"].total
		});

		if (this.model.get('page') == this.model.get('totalPages') && /1$/.test(this.model.get('total'))) {
			this.triggerMethod('addEvent', data.events.event);
		} else {
			data.events.event.forEach(function(value, index, list) {
				this.triggerMethod('addEvent', value);
				
				if (self.model.get('page') == 1 && index == 0) {
					this.triggerMethod('setView', list, param);
				}
			});
		}
	}

});