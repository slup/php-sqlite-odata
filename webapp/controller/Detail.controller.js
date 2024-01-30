sap.ui.define([
	"sap/ui/core/mvc/Controller",
    "sap/ui/core/routing/History"
], (Controller, History) => {
	"use strict";

	return Controller.extend("ch.slup.phpsqliteodata.ui5.controller.Detail", {
		onInit() {
			const oRouter = this.getOwnerComponent().getRouter();
			oRouter.getRoute("detail").attachPatternMatched(this.onObjectMatched, this);

            var oNewNoteModelData = {
                newNote: "",
            };
            
            var oNewNoteModel = new sap.ui.model.json.JSONModel();
            oNewNoteModel.setData(oNewNoteModelData);
            this.getView().setModel(oNewNoteModel, 'newNoteModel');
		},

		onObjectMatched(oEvent) {
			this.getView().bindElement({
				path: "/" + window.decodeURIComponent(oEvent.getParameter("arguments").id)
			});
		},

		_toggleCfgModel : function () {
            var oCfgModel = this.getOwnerComponent().getModel("cfg");
            var oData = oCfgModel.getData();
            var bDataNoSetYet = !oData.hasOwnProperty("inDelete");
            var bInDelete = (bDataNoSetYet) ? true : oData.inDelete;

            oCfgModel.setData({
                inDelete : !bInDelete,
                notInDelete : bInDelete,
                notebookListMode : (!bInDelete) ? "Delete" : "None",
                noteListMode : (!bInDelete) ? "Delete" : "None",
            });
        },

		onEditOrDoneButtonPress : function (oEvent) {
            this._toggleCfgModel();
        },


        addNote : function(oEvent) {
            var oNewNoteModel = this.getView().getModel('newNoteModel');
            var sNote = oNewNoteModel.getProperty('/newNote');
            var iNotebookId = this.getView().byId('detail').getBindingContext().getProperty('id');
            
            var oModel = this.getView().getModel();
            var oNewData = {
                "note" : sNote,
                "notebookId" : iNotebookId
            }
            
            var sPath = this.getView().byId('noteList').getBindingInfo('items').path;
            
            oModel.create('/'+ sPath, oNewData, {
                    success: function(){
                        console.log("Note: Create successful");
                        oNewNoteModel.setProperty('/newNote', '');
                        oModel.refresh();
                    }, error: function(){
                        console.log("Note: Create failed");
                    }
                }
            );
        },

		detailBackPressed : function(oEvent) {
            const oHistory = History.getInstance();
            const sPreviousHash = oHistory.getPreviousHash();

            if (sPreviousHash !== undefined) {
                window.history.go(-1);
            } else {
                const oRouter = this.getOwnerComponent().getRouter();
                oRouter.navTo("overview", {}, true);
            }
        }
	});
});