$.extend(KT.notifications, {
  'customFieldsValidationWarning': {
    type: 'warning',
    title: 'Проверьте введенные значения',
    msg: 'Некоторые значения дополнительных полей не заданы или заданы некорректно',
    killtarget: 'body',
    timeout: 5000
  },
  'tooLargeFileWarning': {
    type: 'warning',
    title: 'Проверьте введенные значения',
    msg: 'Некоторые значения дополнительных полей не заданы или заданы некорректно',
    killtarget: 'body',
    timeout: 1000
  },
  'fileWasntLoadedWarning': {
    type: 'warning',
    title: 'Не удалось загрузить файл',
    msg: 'Проверьте файл на корректность',
    killtarget: 'body',
    timeout: 1000
  }
});
