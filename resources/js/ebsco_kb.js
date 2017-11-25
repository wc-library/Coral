/*
**************************************************************************************************************************
** CORAL Resources Module v. 1.0
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

  updateSearch($('#searchOffset').val());

  //bind change event to each of the page start
  $(".setPage").live('click', function () {
    setPageStart($(this).attr('id'));
  });

  $('#searchType').change(function(){
    updateSearchForm();
  });

  $(".searchButton").click(function() {
    $('#ebscoKbSearchForm').submit();
    return false;
  });

  $('#ebscoKbSearchForm').submit(function(e){
    e.preventDefault();
    updateSearch($('#searchOffset').val());
  });

  $(".newSearch").click(function () {
    //reset fields
    $('#ebscoKbSearchForm input[type=hidden]').not('#searchCount').val("");
    $('#ebscoKbSearchForm input[type=text]').val("");
    $('#ebscoKbSearchForm select').each(function(){
      $(this).val($(this).data('default'));
    });
    updateSearch();
  });

});

function updateSearch(pageNumber) {
  $("#div_feedback").html("<img src='images/circle.gif'>  <span style='font-size:90%'>"+_("Processing...")+"</span>");
  if (!pageNumber) {
    pageNumber = 1;
  }
  $('#searchOffset').val(pageNumber);

  var form = $('#ebscoKbSearchForm');
  $.post(
      form.attr('action'),
      form.serialize(),
      function(html) {
        $("#div_feedback").html("&nbsp;");
        $("#div_searchResults").html(html);
      }
  );

  updateSearchForm();

  window.scrollTo(0, 0);
}

function updateSearchForm() {
  $('.ebsco-toggle-option').hide();
  var selected = $('#searchType').val();
  $('.'+selected+'-option').show();

  if(selected === 'titles'){
    $('label[for="searchName"]').html('<strong>contains</strong>');
  } else {
    $('label[for="searchName"]').html('<strong>Name (contains)</strong>');
  }
}

function setPageStart(pageStartNumber){
  updateSearch(pageStartNumber);
}


function setNumberOfRecords(recordsPerPageNumber){
  $("#searchRecordsPerPage").val(recordsPerPageNumber);
  updateSearch();
}
