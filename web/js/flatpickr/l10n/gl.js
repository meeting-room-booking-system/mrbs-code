(function (global, factory) {
  typeof exports === 'object' && typeof module !== 'undefined' ? factory(exports) :
  typeof define === 'function' && define.amd ? define(['exports'], factory) :
  (global = typeof globalThis !== 'undefined' ? globalThis : global || self, factory(global.gl = {}));
}(this, (function (exports) { 'use strict';

  var fp = typeof window !== "undefined" && window.flatpickr !== undefined
      ? window.flatpickr
      : {
          l10ns: {},
      };
  var Galician = {
      weekdays: {
          shorthand: ["Dom", "Lun", "Mar", "Mér", "Xov", "Ven", "Sáb"],
          longhand: [
              "Domingo",
              "Luns",
              "Martes",
              "Mércores",
              "Xoves",
              "Venres",
              "Sábado",
          ],
      },
      months: {
          shorthand: [
              "Xan",
              "Feb",
              "Mar",
              "Abr",
              "Mai",
              "Xuñ",
              "Xul",
              "Ago",
              "Set",
              "Out",
              "Nov",
              "Dec",
          ],
          longhand: [
              "Xaneiro",
              "Febreiro",
              "Marzo",
              "Abril",
              "Maio",
              "Xuño",
              "Xullo",
              "Agosto",
              "Setembro",
              "Outubro",
              "Novembro",
              "Decembro",
          ],
      },
      ordinal: function () {
          return "º";
      },
      firstDayOfWeek: 1,
      rangeSeparator: " a ",
      time_24hr: true,
  };
  fp.l10ns.gl = Galician;
  var gl = fp.l10ns;

  exports.Galician = Galician;
  exports.default = gl;

  Object.defineProperty(exports, '__esModule', { value: true });

})));
