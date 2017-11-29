/*
**************************************************************************************************************************
** CORAL Resources Module v. 1.2
**
** Copyright (c) 2010 University of Notre Dame
**
** This file is part of CORAL.
**
** CORAL is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
**
** CORAL is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License along with CORAL.  If not, see <http://www.gnu.org/licenses/>.
**
**************************************************************************************************************************
*/

$(document).ready(function(){

	$('#ebscoKbTitleImportForm').submit(function(e){
		e.preventDefault();
		processEbscoKbImport('save', $(this));
	});

  $("#providerText").autocomplete('ajax_processing.php?action=getOrganizationList', {
    minChars: 2,
    max: 20,
    mustMatch: false,
    width: 223,
    delay: 10,
    matchContains: true,
    formatItem: function(row) {
      return "<span style='font-size: 80%;'>" + row[0] + "</span>";
    },
    formatResult: function(row) {
      return row[0].replace(/(<.+?>)/gi, '');
    }

  });

  //once something has been selected, change the hidden input value
  $("#providerText").result(function(event, data, formatted) {
    $('#organizationID').val(data[1]);
  });


});

function processEbscoKbImport(status,form){

  var data = $(form).serializeArray();
  data.push({name: 'resourceStatus', value: status});
  $.ajax({
    type:       "POST",
    url:        "ajax_processing.php?action=importFromEbscoKb",
    cache:      false,
    data:       $.param(data),
    success:    function(resourceID) {
      //go to the new resource page if this was submitted
      if (status == 'progress') {
        //$('#div_ebscoKbTitleImportForm').html('<pre>'+resourceID+'</pre>');
        window.parent.location=("resource.php?ref=new&resourceID=" + resourceID);
        tb_remove();
        return false;
      } else {
        window.parent.location=("queue.php?ref=new");
        tb_remove();
        return false;
      }
    }
  });

}