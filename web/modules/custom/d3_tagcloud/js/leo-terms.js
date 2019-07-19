(function($, Drupal) {

  Drupal.leoTerms = Drupal.leoTerms || {};


  Drupal.leoTerms.init = function(_settings) {
    Drupal.leoTerms.canvasHeight = 300;
    Drupal.leoTerms.canvasWidth = 700;
    Drupal.leoTerms.termsList = _settings.leoTerms.termsList;

    for (var i = 0; i < Drupal.leoTerms.termsList.length; i++) {
      Drupal.leoTerms.termsList[i].size = 10 * (1 + Drupal.leoTerms.termsList[i].importance / 10);
    }


    d3.layout.cloud()
      .size([Drupal.leoTerms.canvasWidth, Drupal.leoTerms.canvasHeight])
      .words(Drupal.leoTerms.termsList)
      .padding(1)
      .rotate(function() {
        return ~~ (Math.random() * 2) * 90;
      })
      .fontSize(function(_term) {
        return _term.size;
      })
      .on("end", Drupal.leoTerms.drawTerms)
      .start();
  };

  Drupal.leoTerms.drawTerms = function() {
    d3.select(".d3-term-cloud")
      .append("svg")
      .attr("class", "leo-cloud-svg")
      .attr("width", Drupal.leoTerms.canvasWidth)
      .attr("height", Drupal.leoTerms.canvasHeight)
      .append("g")
      .attr("transform", "translate(" + Drupal.leoTerms.canvasWidth / 2 + "," + Drupal.leoTerms.canvasHeight / 2 + ")")
      .selectAll("text")
      .data(Drupal.leoTerms.termsList)
      .enter()
      .append("text")
      .style("font-size", function(_term) {
        return _term.size + "px";
      })
      .style("cursor", function (_term, i) {
        return _term.link ? 'pointer' : 'unset';
      })
      .style("fill", function(_term, i) {
        let colorNum = Math.round(Math.random() * 100) % 2;
        let color = 'white';
        switch (colorNum) {
          case 0:
            color = '#434343';
            break;

          case 1:
            color = '#636363';
            break;
        }

        return color;
      })
      .attr("text-anchor", "middle")
      .attr("transform", function(_term) {
        return "translate(" + [_term.x, _term.y] + ")rotate(" + _term.rotate + ")";
      })
      .text(function(_term) {
        return _term.text;
      })
      .on("click", function (_term, i){
        if (_term.link) {
          window.open(_term.link, "_blank");
        }
      });
  };

  Drupal.behaviors.leoTerms = {
    attach: function(context, settings) {
      $('.d3-term-cloud').once('leoCloud').each(function () {
        Drupal.leoTerms.init(settings);
      });
    }
  };

}(jQuery, Drupal));

