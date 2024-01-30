sap.ui.define([
	"sap/ui/core/mvc/Controller",
    "sap/ui/model/Filter",
    "sap/ui/model/FilterOperator",
    "sap/ui/core/Fragment",
    "sap/m/MessageBox",
    "sap/m/MessageToast",
	"../model/formatter"
], function(Controller, Filter, FilterOperator, Fragment, MessageBox, MessageToast, formatter) {
	"use strict";

	return Controller.extend("ch.slup.phpsqliteodata.ui5.controller.Home", {

		formatter: formatter,

		onInit: function () {
            this._toggleCfgModel();
            /*
            // set initial ui configuration model
            var oCfgModel = new sap.ui.model.json.JSONModel({});
            this.getView().setModel(oCfgModel, "cfg");
            this._toggleCfgModel();


            var oInputModel = new sap.ui.model.json.JSONModel({
                    "NewListItem" : { 
                        "title" : "" 
                    },
                    "NewList" : {
                        title : ""
                    }
                });
            this.getView().setModel(oInputModel, "inputModel");

            var oNewNotebookModelData = {
                newTitle: "",
            };
            
            var oNewNotebookModel = new sap.ui.model.json.JSONModel();
            oNewNotebookModel.setData(oNewNotebookModelData);
            this.getView().setModel(oNewNotebookModel, 'newNotebookModel');
            
            var oNewNoteModelData = {
                newNote: "",
            };
            
            var oNewNoteModel = new sap.ui.model.json.JSONModel();
            oNewNoteModel.setData(oNewNoteModelData);
            this.getView().setModel(oNewNoteModel, 'newNoteModel');
            */
		},

        _toggleCfgModel : function () {
            //var oCfgModel = this.getView().getModel("cfg");
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

        addNotebook : function(oEvent) {
            var oNewNotebookModel = this.getView().getModel('newNotebookModel');
            var sTitle = oNewNotebookModel.getProperty('/newTitle');
            
            var oModel = this.getView().getModel();
            var oNewData = {
                "title" : sTitle,
            }
            
            var sPath = this.getView().byId('notebookList').getBindingInfo('items').path;
            
            oModel.create(sPath, oNewData, null, function(){
                    console.log("Notebook: Create successful");
                    oNewNotebookModel.setProperty('/newTitle', '');
                },function(){
                    console.log("Notebook: Create failed");
                }
            );
        },
        
        deleteElement : function(oEvent) {
            var oModel = this.getView().getModel();
            var sPath = oEvent.getParameters().listItem.getBindingContext().getPath();
            
            oModel.remove(sPath, 
                { 
                    success: function(oData, response){
                        console.log("Delete successful");
                    },
                    error: function(oError) {
                        console.log("Delete failed");
                    }
                }
            );
        },
        
        onEditOrDoneButtonPress : function (oEvent) {
            this._toggleCfgModel();
        },
        
        notebookSelected : function(oEvent) {
            //var oApp = this.getView().byId('app');
            //oApp = this.getOwnerComponent().getRootControl().byId("app");
            //oApp = this.byId("master").getParent().getParent();

            const oBindingContext = oEvent.getSource().getBindingContext();
            const oRouter = this.getOwnerComponent().getRouter();
            oRouter.navTo("detail", {
                id: window.encodeURIComponent(oBindingContext.getPath().substr(1))
            });

            //this.getOwnerComponent().getRouter().navTo("detail");

            //var oDetailPage = this.getView().byId('detail');
            
            //oDetailPage.setBindingContext(oEvent.getSource().getBindingContext());
            
            //oApp.to('detail', 'slide', null, null);
        },

        /*

        onRefresh: function (oEvent) {
            var oModel = this.getView().getModel();
            if (oModel) {
                oModel.refresh();
            }
        },

        onNewListDialog: function (oEvent) {
            // create dialog lazily
            const oView = this.getView();
            if (!this.oNewListDialog) {
                this.oNewListDialog = this.loadFragment({
                    name: "ch.slup.phpsqliteodata.ui5.view.fragment.Entry"
                }).then(function (oTheDialog) {
                    oView.addDependent(oTheDialog);

                    return oTheDialog;
                });
            } 

            this.oNewListDialog.then(function(oTheDialog) {
                oTheDialog.open();
            });
        },

        onAddNewList: function (oEvent) {
            const oInputModel = this.getView().getModel("inputModel");
            const sTitle = oInputModel.getProperty("/NewList/title");
            const oModel = this.getView().getModel();
            const sPath = this.getView().byId('shoppingListCrousel').getBindingInfo('pages').path;
            const oController = this;
            const oNewData = {
                "title" : sTitle,
                "listOrder" : -1
            };
            
            oModel.create(sPath, 
                oNewData, 
                {
                    success: function(oData, response){
                        console.log("List: Create successful");
                        oInputModel.setProperty('/NewList/title', '');
                        if (oController.oNewListDialog) {
                            oController.oNewListDialog.then(function(oTheDialog) {
                                oTheDialog.close();
                            });
                        }
                    },
                    error: function(oError){
                        console.log("List: Create failed");
                        MessageToast.show("Erstellen fehlgeschlagen");
                    }
                }
            );
        },

        onCloseNewListDialog: function (oEvent) {
            if (this.oNewListDialog) {
                this.oNewListDialog.then(function(oTheDialog) {
                    oTheDialog.close();
                });
            }
        },


        onRemoveList: function (oEvent) {
            const oController = this;
            const oModel = this.getView().getModel();
            const iShoplistId = oEvent.getSource().getBindingContext().getProperty("id");
            const sPath = oEvent.getSource().getBindingContext().getPath();

            sap.m.MessageBox.confirm("Soll diese Einkaufsliste gelöscht werden?", {
                title: "Bestätigen",
                onClose: function(oAction) {
                    if (sap.m.MessageBox.Action.OK === oAction) {
                        oModel.remove(sPath, 
                            { 
                                success: function(){
                                    MessageToast.show("Liste gelöscht");
                                    console.log("Delete successful");
                                },
                                error: function() {
                                    MessageToast.show("Löschen fehlgeschlagen");
                                    console.log("Delete failed");
                                }
                            }
                        )
                    } else {
                        //MessageToast.show("Abbruch");
                    }
                }
            });
        },

        addListItem: function (oEvent) {
            const oInputModel = this.getView().getModel("inputModel");
            const sTitle = oInputModel.getProperty("/NewListItem/title");
            this._addListItem(oEvent, sTitle);
        },

        addListItemFromSuggest: function(oEvent) {
            if (oEvent && oEvent.getParameter("selectedItem")) {
                const oItem = oEvent.getParameter("selectedItem");
                const sTitle = oItem.getText();
                this._addListItem(oEvent, sTitle);
            }
        },

        onUpload: function(e) {
            var fU = this.getView().byId("idfileUploader");
            var domRef = fU.getFocusDomRef();
            var file = domRef.files[0];
            var reader = new FileReader();
            var jsonObj = "jsonObject=";
            reader.onload = function(oEvent) {
                var strCSV = oEvent.target.result;
                var arrCSV = strCSV.match(/[\w .]+(?=,?)/g);
                var noOfCols = 3; // adjust number of columns depending upon the expected csv
                var headerRow = arrCSV.splice(0, noOfCols);
                var data = [];
                while (arrCSV.length > 0) {
                    var obj = {};
                    var row = arrCSV.splice(0, noOfCols);
                    for (var i = 0; i < row.length; i++) {
                        obj[headerRow[i]] = row[i].trim();
                    }
                    data.push(obj);
                }
                var Len = data.length;
                data.reverse();
                params += "[";
                for (var j = 0; j < Len; j++) {
                    jsonObj += JSON.stringify(data.pop()) + ", ";
                }
                jsonObj = jsonObj.substring(0, jsonObj.length - 2);
                jsonObj += "]";
                    return jsonObj;
            };
            reader.readAsBinaryString(file);
        },

        _addListItem: function(oEvent, sTitle) {
            const oController = this;
            const oModel = this.getView().getModel();
            const oInputModel = this.getView().getModel("inputModel");
            const oSuggestModel = this.getView().getModel("suggestModel");
            const iShoplistId = oEvent.getSource().getBindingContext().getProperty("id");
            //const sTitle = oInputModel.getProperty("/NewListItem/title");
            const oNewData = {
                    "itemCount" : 0,
                    "title" : sTitle,
                    "done" : false,
                    "lastModified" : new Date(),
                    "shoplistId" : iShoplistId
                }

            const sPath = this.getView().byId('shoppingListItems').getBindingInfo('items').path;
            
            if (!sTitle || sTitle.length == 0) {
                return;
            }

            oModel.create("/"+sPath, 
                oNewData, 
                {
                    success: function(oData, response){
                        console.log("ListItem: Create successful");
                        //oInputModel.setProperty('/NewListItem/count', 0);
                        oInputModel.setProperty('/NewListItem/title', '');
                        //oModel.refresh();
                        const aSuggestions = oSuggestModel.getProperty('/Suggestions');
                        const setSuggestions = new Set(aSuggestions.map(function (e) { return e.title }));
                        if (!setSuggestions.has(sTitle)) {
                            aSuggestions.push({ "title" : sTitle });
                            oSuggestModel.setProperty('/Suggestions', aSuggestions);
                            oController._saveSuggestItems();
                        }
                    },
                    error: function(oError){
                        console.log("ListItem: Create failed");
                    }
                }
            );
        },

        listItemPressed: function(oEvent) {
            var sPath = oEvent.getSource().getBindingContext().getPath();
            var oModel = this.getView().getModel();

            var oUpdateData = oModel.getObject(sPath);
            oUpdateData.done = !oUpdateData.done;

            oModel.update(sPath, 
                oUpdateData, 
                { 
                    success: function(){
                        console.log("ListItem: Update successful");
                        //oModel.refresh();
                    },
                    error: function(){
                        console.log("ListItem: Update failed");
                    }
                }
            );
        },

        onClearCompleted: function (oEvent) {
            var oBindingContext = oEvent.getSource().getBindingContext();
            var iShoplistId = oEvent.getSource().getBindingContext().getProperty("id");
            var sPath = oBindingContext.getPath();

            var oModel = this.getView().getModel();
            
            // holen aller items zu der shoppinglist
            oModel.read(sPath + "/ListItem", { 
                    success: function(oData, response){
                        console.log("ListItems: Read successful");
                        
                        if (oData && oData.results) {
                            var aDoneIds = [];

                            // filtern auf done=true
                            oData.results.forEach(element => {
                                    if (element.done) {
                                        aDoneIds.push(element.id);
                                    }
                                });

                            // löschen aller gefilterten items
                            aDoneIds.forEach(element => {
                                var sPath = "/ListItem("+element+")"
                                oModel.remove(sPath, 
                                    {
                                        success: function(){
                                            console.log("Delete successful");
                                        },
                                        error: function() {
                                            console.log("Delete failed");
                                        }
                                    });
                                });
                        }
                        
                        
                    },
                    error: function(oError){
                        console.log("ListItems: Read failed");
                    }
                });

            
            
        },

        onSuggest: function (oEvent) {
            var sTerm = oEvent.getParameter("suggestValue");
            var aFilters = [];
            if (sTerm) {
                aFilters.push(new Filter("title", FilterOperator.StartsWith, sTerm));
            }

            oEvent.getSource().getBinding("suggestionItems").filter(aFilters);
        },

        _loadSuggestItems() {
            var sSuggestItems = localStorage.getItem("suggestItems");
            var oSuggestItems = {"Suggestions" : []};
            try {
                oSuggestItems = JSON.parse(sSuggestItems) || {"Suggestions" : []};
            } catch (e) {
                jQuery.sap.log.error(e.message);
            }

            var oSuggestModel = new sap.ui.model.json.JSONModel(oSuggestItems);
            this.getView().setModel(oSuggestModel, "suggestModel");
        },

        _saveSuggestItems() {
            var oSuggestModel = this.getView().getModel("suggestModel");
            var sData = oSuggestModel.getJSON();
            try {
                localStorage.setItem("suggestItems", sData);
            } catch (e) {
                jQuery.sap.log.error(e.message);
            }
            
        }
        */
	});
});