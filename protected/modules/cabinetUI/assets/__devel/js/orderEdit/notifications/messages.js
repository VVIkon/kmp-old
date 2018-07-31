$.extend(KT.notifications,{
  'linkingTourists': {
    type: 'success',
    title: 'Выполняется привязка туристов...',
    msg: '',
    killtarget: 'body',
    timeout: 1000
  },
  'touristsLinked': {
    type: 'success',
    title: 'Привязка туристов прошла успешно!',
    msg: '',
    killtarget: 'body'
  },
  'bookingChanged': {
    type: 'success',
    title: 'Данные брони успешно изменены',
    msg: '',
    killtarget: 'body'
  },
  'bookingStarted': {
    type: 'success',
    title: 'Бронирование началось!',
    msg: 'Процесс бронирования успешно запущен',
    killtarget: 'body'
  },
  'bookingFinished': {
    type: 'success',
    title: 'Бронирование завершено!',
    msg: 'Услуга успешно забронирована',
    killtarget: 'body'
  },
  'bookingCancelled': {
    type: 'success',
    title: 'Бронь отменена!',
    msg: 'Отмена брони прошла успешно',
    killtarget: 'body'
  },
  'ticketsIssued': {
    type: 'success',
    title: 'Выписка билетов совершена успешно!',
    msg: 'Маршрутная квитанция отправлена на указанный e-mail',
    killtarget: 'body',
    timeout: 10000
  },
  'touristAdded': {
    type: 'success',
    title: 'Информация сохранена успешно!',
    msg: 'Турист <b>{{name}}</b> добавлен в заявку',
    killtarget: 'body'
  },
  'touristUpdated': {
    type: 'success',
    title: 'Информация сохранена успешно!',
    msg: 'Информация по туристу <b>{{name}}</b> обновлена',
    killtarget: 'body'
  },
  'touristRemoved': {
    type :'success',
    title: 'Операция выполнена успешно!',
    msg: 'Турист <b>{{name}}</b> удален из заявки',
    killtarget: 'body'
  },
  'documentUploaded': {
    type: 'success',
    title: 'Документ успешно загружен!',
    msg: '',
    ontop: true,
    killtarget: 'body'
  },
  'discountSet': {
    type: 'success',
    title: 'Скидка успешно выставлена!',
    msg: 'Новый размер скидки: {{discount}}',
    killtarget: 'body'
  },
  'reservationDataChanged': {
    type: 'success',
    title: 'Данные брони успешно изменены!',
    msg: '',
    killtarget: 'body'
  },
  'serviceDataChanged': {
    type:'success',
    title:'Данные услуги успешно изменены!',
    msg:'',
    killtarget: 'body'
  },
  'serviceStatusSet': {
    type: 'success',
    title: 'Статус услуги успешно изменен!',
    msg: '',
    killtarget: 'body'
  },
  'ticketsDataChanged': {
    type: 'success',
    title: 'Данные билетов успешно изменены!',
    msg: '',
    killtarget: 'body'
  },
  'invoiceCancelled': {
    type: 'success',
    title: 'Счет успешно отменен!',
    msg: '',
    killtarget: 'body'
  },
  'userSaved': {
    type: 'success',
    title: 'Данные сотрудника успешно сохранены!',
    msg: '',
    killtarget: 'body'
  },
  'customFieldsSaved': {
    type: 'success',
    title: 'Внесенные данные успешно сохранены!',
    msg: '',
    killtarget: 'body'
  },
  'serviceSetToManual': {
    type: 'success',
    title: 'Услуга переведена в обработку',
    msg: ''
  },
  'serviceStatusRenewed': {
    type: 'success',
    title: 'Статус услуги синхронизирован',
    msg: '',
    killtarget: 'body'
  },
  'historyReportSent': {
    type: 'success',
    title: 'Отчет отправлен',
    msg: 'Отчет с историей заявки отправлен на {{email}}'
  },
  'documentSent': {
    type: 'success',
    title: 'Документ отправлен',
    msg: 'Документ {{document}} отправлен на {{email}}'
  },
  'serviceInProcess': {
    type: 'success',
    title: 'Процесс выполняется',
    msg: 'Ожидайте уведомления о завершении по почте'
  }
});
