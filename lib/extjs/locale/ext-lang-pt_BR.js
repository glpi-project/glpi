/*
 * Ext JS Library 2.1
 * Copyright(c) 2006-2008, Ext JS, LLC.
 * licensing@extjs.com
 * 
 * http://extjs.com/license
 */

/*
 * Portuguese/Brazil Translation by Weber Souza
 * 08 April 2007
 * Updated by Allan Brazute Alves (EthraZa)
 * 06 September 2007
 */

Ext.UpdateManager.defaults.indicatorText = '<div class="loading-indicator">Carregando...</div>';

if(Ext.View){
   Ext.View.prototype.emptyText = "";
}

if(Ext.grid.Grid){
   Ext.grid.Grid.prototype.ddText = "{0} linha(s) selecionada(s)";
}

if(Ext.TabPanelItem){
   Ext.TabPanelItem.prototype.closeText = "Fechar";
}

if(Ext.form.Field){
   Ext.form.Field.prototype.invalidText = "O valor para este campo é inválido";
}

if(Ext.LoadMask){
    Ext.LoadMask.prototype.msg = "Carregando...";
}

Date.monthNames = [
   "Janeiro",
   "Fevereiro",
   "Março",
   "Abril",
   "Maio",
   "Junho",
   "Julho",
   "Agosto",
   "Setembro",
   "Outubro",
   "Novembro",
   "Dezembro"
];

Date.getShortMonthName = function(month) {
  return Date.monthNames[month].substring(0, 3);
};

Date.monthNumbers = {
  Jan : 0,
  Fev : 1,
  Mar : 2,
  Abr : 3,
  Mai : 4,
  Jun : 5,
  Jul : 6,
  Ago : 7,
  Set : 8,
  Out : 9,
  Nov : 10,
  Dez : 11
};

Date.getMonthNumber = function(name) {
  return Date.monthNumbers[name.substring(0, 1).toUpperCase() + name.substring(1, 3).toLowerCase()];
};

Date.dayNames = [
   "Domingo",
   "Segunda",
   "Terça",
   "Quarta",
   "Quinta",
   "Sexta",
   "Sábado"
];

if(Ext.MessageBox){
   Ext.MessageBox.buttonText = {
      ok     : "OK",
      cancel : "Cancelar",
      yes    : "Sim",
      no     : "Não"
   };
}

if(Ext.util.Format){
   Ext.util.Format.date = function(v, format){
      if(!v) return "";
      if(!(v instanceof Date)) v = new Date(Date.parse(v));
      return v.dateFormat(format || "d/m/Y");
   };
}

if(Ext.DatePicker){
   Ext.apply(Ext.DatePicker.prototype, {
      todayText         : "Hoje",
      minText           : "Esta data é anterior a menor data",
      maxText           : "Esta data é posterior a maior data",
      disabledDaysText  : "",
      disabledDatesText : "",
      monthNames        : Date.monthNames,
      dayNames          : Date.dayNames,
      nextText          : 'Próximo Mês (Control+Direita)',
      prevText          : 'Mês Anterior (Control+Esquerda)',
      monthYearText     : 'Escolha um Mês (Control+Cima/Baixo para mover entre os anos)',
      todayTip          : "{0} (Espaço)",
      format            : "d/m/Y",
      okText            : "&#160;OK&#160;",
      cancelText        : "Cancelar",
      startDay          : 0
   });
}

if(Ext.PagingToolbar){
   Ext.apply(Ext.PagingToolbar.prototype, {
      beforePageText : "Página",
      afterPageText  : "de {0}",
      firstText      : "Primeira Página",
      prevText       : "Página Anterior",
      nextText       : "Próxima Página",
      lastText       : "Última Página",
      refreshText    : "Atualizar",
      displayMsg     : "<b>{0} à {1} de {2} registro(s)</b>",
      emptyMsg       : 'Sem registros para exibir'
   });
}

if(Ext.form.TextField){
   Ext.apply(Ext.form.TextField.prototype, {
      minLengthText : "O tamanho mínimo para este campo é {0}",
      maxLengthText : "O tamanho máximo para este campo é {0}",
      blankText     : "Este campo é obrigatório.",
      regexText     : "",
      emptyText     : null
   });
}

if(Ext.form.NumberField){
   Ext.apply(Ext.form.NumberField.prototype, {
      minText : "O valor mínimo para este campo é {0}",
      maxText : "O valor máximo para este campo é {0}",
      nanText : "{0} não é um número válido"
   });
}

if(Ext.form.DateField){
   Ext.apply(Ext.form.DateField.prototype, {
      disabledDaysText  : "Desabilitado",
      disabledDatesText : "Desabilitado",
      minText           : "A data deste campo deve ser posterior a {0}",
      maxText           : "A data deste campo deve ser anterior a {0}",
      invalidText       : "{0} não é uma data válida - deve ser informado no formato {1}",
      format            : "d/m/Y"
   });
}

if(Ext.form.ComboBox){
   Ext.apply(Ext.form.ComboBox.prototype, {
      loadingText       : "Carregando...",
      valueNotFoundText : undefined
   });
}

if(Ext.form.VTypes){
   Ext.apply(Ext.form.VTypes, {
      emailText    : 'Este campo deve ser um endereço de e-mail válido, no formado "usuario@dominio.com.br"',
      urlText      : 'Este campo deve ser uma URL no formato "http:/'+'/www.dominio.com.br"',
      alphaText    : 'Este campo deve conter apenas letras e _',
      alphanumText : 'Este campo deve conter apenas letras, números e _'
   });
}

if(Ext.form.HtmlEditor){
   Ext.apply(Ext.form.HtmlEditor.prototype, {
	 createLinkText : 'Porfavor, entre com a URL do link:',
	 buttonTips : {
            bold : {
               title: 'Negrito (Ctrl+B)',
               text: 'Deixa o texto selecionado em negrito.',
               cls: 'x-html-editor-tip'
            },
            italic : {
               title: 'Italico (Ctrl+I)',
               text: 'Deixa o texto selecionado em italico.',
               cls: 'x-html-editor-tip'
            },
            underline : {
               title: 'Sublinhado (Ctrl+U)',
               text: 'Sublinha o texto selecionado.',
               cls: 'x-html-editor-tip'
           },
           increasefontsize : {
               title: 'Aumentar Texto',
               text: 'Aumenta o tamanho da fonte.',
               cls: 'x-html-editor-tip'
           },
           decreasefontsize : {
               title: 'Diminuir Texto',
               text: 'Diminui o tamanho da fonte.',
               cls: 'x-html-editor-tip'
           },
           backcolor : {
               title: 'Cor de Fundo',
               text: 'Muda a cor do fundo do texto selecionado.',
               cls: 'x-html-editor-tip'
           },
           forecolor : {
               title: 'Cor da Fonte',
               text: 'Muda a cor do texto selecionado.',
               cls: 'x-html-editor-tip'
           },
           justifyleft : {
               title: 'Alinhar à Esquerda',
               text: 'Alinha o texto à esquerda.',
               cls: 'x-html-editor-tip'
           },
           justifycenter : {
               title: 'Centralizar Texto',
               text: 'Centraliza o texto no editor.',
               cls: 'x-html-editor-tip'
           },
           justifyright : {
               title: 'Alinhar à Direita',
               text: 'Alinha o texto à direita.',
               cls: 'x-html-editor-tip'
           },
           insertunorderedlist : {
               title: 'Lista com Marcadores',
               text: 'Inicia uma lista com marcadores.',
               cls: 'x-html-editor-tip'
           },
           insertorderedlist : {
               title: 'Lista Numerada',
               text: 'Inicia uma lista numerada.',
               cls: 'x-html-editor-tip'
           },
           createlink : {
               title: 'Hyperligação',
               text: 'Transforma o texto selecionado em um hyperlink.',
               cls: 'x-html-editor-tip'
           },
           sourceedit : {
               title: 'Editar Fonte',
               text: 'Troca para o modo de edição de código fonte.',
               cls: 'x-html-editor-tip'
           }
        }
   });
}

if(Ext.grid.GridView){
   Ext.apply(Ext.grid.GridView.prototype, {
      sortAscText  : "Ordem Ascendente",
      sortDescText : "Ordem Descendente",
      lockText     : "Bloquear Coluna",
      unlockText   : "Desbloquear Coluna",
      columnsText  : "Colunas"
   });
}

if(Ext.grid.PropertyColumnModel){
   Ext.apply(Ext.grid.PropertyColumnModel.prototype, {
      nameText   : "Nome",
      valueText  : "Valor",
      dateFormat : "d/m/Y"
   });
}

if(Ext.layout.BorderLayout.SplitRegion){
   Ext.apply(Ext.layout.BorderLayout.SplitRegion.prototype, {
      splitTip            : "Arraste para redimencionar.",
      collapsibleSplitTip : "Arraste para redimencionar. Duplo clique para esconder."
   });
}
