(function(global, extendApiClient) {

    extendApiClient(KT.apiClient);

}(this,function(ApiClient) {

  /**
  * Отправка отчета по почте
  * @param {Object} params - параметры 
  */
  ApiClient.sendReportFile = function(params) {
    return KT.rest({
        caller:"orderEdit - sendReportFile",
        data: params,
        url: this.urls.sendReportFile
      });
  };

  /**
  * Отправка документа по почте
  * @param {Object} params - параметры 
  */
  ApiClient.sendDocumentToUser = function(params) {
    return KT.rest({
      caller:"orderEdit - sendDocumentToUser",
      data: params,
      url: this.urls.sendDocumentToUser
    });
  };

}));