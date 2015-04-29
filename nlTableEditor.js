/**
 * Created by Marc on 29.04.2015.
 */

(function(){
    var app = angular.module('app', []);

    app.controller('controller', function($scope, $http){
        $scope.page = 1;

        api('action=tables', function(tables){
            $scope.tables = tables;
        });

        $scope.changeTable = function(table){
            $scope.page = 1;

            api('action=columns&table=' + table, function(columns){
                $scope.columns = columns;
            });

            loadRows();
        };

        $scope.previous = function(){
            if($scope.page > 1){
                $scope.page--;

                loadRows();
            }
        };

        $scope.next = function(){
            if($scope.rows.length > 0){
                $scope.page++;

                loadRows();
            }
        };

        function api(query, cb){
            $http.get('nlTableEditor.php?' + query).success(cb);
        }

        function loadRows(){
            api('action=rows&table=' + $scope.table + '&page=' + $scope.page, function(rows){
                $scope.rows = rows;
            });
        }
    });
}());