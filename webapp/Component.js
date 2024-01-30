sap.ui.define([
	"sap/ui/core/UIComponent",
	"sap/ui/Device",
	"./model/models"
], function(UIComponent, Device, models) {
	"use strict";

	return UIComponent.extend("ch.slup.phpsqliteodata.ui5.Component", {

		metadata: {
			manifest: "json"
		},

		/**
		 * The component is initialized by UI5 automatically during the startup of the app and calls the init method once.
		 * @public
		 * @override
		 */
		init: function() {
			// call the base component's init function
			UIComponent.prototype.init.apply(this, arguments);

			// set the device model
			this.setModel(models.createDeviceModel(), "device");

			// create the views based on the url/hash
			this.getRouter().initialize();

			// set initial ui configuration model
            var oCfgModel = new sap.ui.model.json.JSONModel({});
            this.setModel(oCfgModel, "cfg");

            var oInputModel = new sap.ui.model.json.JSONModel({
                    "NewListItem" : { 
                        "title" : "" 
                    },
                    "NewList" : {
                        title : ""
                    }
                });
            this.setModel(oInputModel, "inputModel");

            var oNewNotebookModelData = {
                newTitle: "",
            };
            
            var oNewNotebookModel = new sap.ui.model.json.JSONModel();
            oNewNotebookModel.setData(oNewNotebookModelData);
            this.setModel(oNewNotebookModel, 'newNotebookModel');
            
            var oNewNoteModelData = {
                newNote: "",
            };
            
            var oNewNoteModel = new sap.ui.model.json.JSONModel();
            oNewNoteModel.setData(oNewNoteModelData);
            this.setModel(oNewNoteModel, 'newNoteModel');
		}
	});
});