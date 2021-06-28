var express =  require('express');
var app = express();
var appRoot = require('app-root-path');
var expressValidator = require('express-validator');
var jwt = require('jsonwebtoken');
var constant = require(appRoot +  "/config/constant");
var bodyParser = require('body-parser');
const fileUpload = require('express-fileupload');
app.use(bodyParser.json({limit:'1500mb'}));
app.use(bodyParser.urlencoded({limit:'1500mb',extended:true}));
var model = require(appRoot + "/lib/model");


