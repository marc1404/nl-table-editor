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

            api('action=columns&table=' + table, function(data){
                $scope.primary = data.primary;
                $scope.columns = data.columns;
            });

            loadRows();
        };

        $scope.previous = function(){
            if($scope.page > 1){
                $scope.page--;
                $scope.selected = null;

                loadRows();
            }
        };

        $scope.next = function(){
            if($scope.rows.length > 0){
                $scope.page++;
                $scope.selected = null;

                loadRows();
            }
        };

        $scope.select = function(row){
            $scope.selected = row;
        };

        $scope.isSelected = function(row){
            return row === $scope.selected;
        };

        $scope.save = function(row){
            api('action=save&table=' + $scope.table + '&primary=' + $scope.primary + '&row=' + JSON.stringify(row, null , 0), function(){
                $scope.selected = null;
            });
        };

        $scope.addRow = function(){
            api('action=add&table=' + $scope.table, function(id){
                var row = { id: id };
                $scope.selected = row;

                $scope.rows.unshift(row);
            });
        };

        $scope.delete = function(row){
             api('action=delete&table=' + $scope.table + '&primary=' + $scope.primary + '&value=' + row[$scope.primary], function(){
                var pos = $scope.rows.indexOf(row);

                $scope.selected = null;

                $scope.rows.splice(pos, 1);
             });
        };

        function api(query, cb){
            $http.get('api.php?' + query).success(cb);
        }

        function loadRows(){
            api('action=rows&table=' + $scope.table + '&page=' + $scope.page, function(rows){
                $scope.rows = rows;
            });
        }
    });
}());