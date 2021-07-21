var express = require('express');
var bodyParser = require('body-parser');

var app = express();
var port = 3000;
var Request = require("request");
var localStorage = require('localStorage');

app.use('/static', express.static('public'))

var userDraftLocalData = [];
var NBAuserDraftLocalData = [];
var intervalUserTime;
var NBAintervalUserTime;
var getDraftLive;
var NBAgetDraftLive;
// var base_url = 'http://159.65.8.30/propskills/';
// var nba_base_url = 'http://159.65.8.30/propskills/api/nba/';

var base_url = 'http://localhost/propskills/';
var nba_base_url = 'http://localhost/propskills/api/nba/';

var cors = require('cors')

app.use(function (request, response, next) {
  response.header("Access-Control-Allow-Origin", "*");
  response.header("Access-Control-Allow-Headers", "Origin, X-Requested-With, Content-Type, Accept");
  next();
});

// parse application/x-www-form-urlencoded
app.use(bodyParser.urlencoded({ extended: true, limit: '500mb' }))

// parse application/json
app.use(bodyParser.json())

var http = require('http').Server(app);
var io = require('socket.io')(http);
io.on('connection', function (socket) {

  socket.on("disconnect", function (data) {
    index = -1;
    userData = JSON.parse(localStorage.getItem('NBAuserDraftLocalData'));
    if (userData) {
      index = userData.map(function (el) { return el.socketId; }).indexOf(socket.id);
    }

    if (index != -1) {
      Request.post({
        "headers": { "content-type": "application/json" },
        "url": nba_base_url + "snakeDrafts/changeUserStatus",
        "body": JSON.stringify({
          "ContestGUID": userData[index].ContestGUID,
          "UserGUID": userData[index].UserGUID,
          "DraftUserStatus": 'Offline'
        })
      }, (error, response, body) => {
        if (error) {
          //return console.dir(error);
        }
        // console.dir(body);

        responseData = JSON.parse(body);
        if (responseData.ResponseCode == 200) {

          Request.post({
            "headers": { "content-type": "application/json" },
            "url": nba_base_url + "snakeDrafts/getRounds",
            "body": JSON.stringify({
              "SeriesGUID": userData[index].SeriesGUID,
              "ContestGUID": userData[index].ContestGUID,
            })
          }, (error, response, body) => {
            if (error) {
              //return console.dir(error);
            }
            //console.dir(body);
            draftJoinedContestUser = JSON.parse(body);
            if (draftJoinedContestUser.ResponseCode == 200) {
              io.to('NBAdraft_' + userData[index].ContestGUID).emit('NBAdraftJoinedContestUser', { ContestGUID: userData[index].ContestGUID, draftJoinedContestUser: draftJoinedContestUser, UserGUID: userData[index].UserGUID, UserStatus: 'Offline' });
              //console.log(index, 'index')
              //socket.leave('auction_'+userData[index].ContestGUID);
              userData.splice(index, 1);
              localStorage.setItem('NBAuserDraftLocalData', JSON.stringify(userData));
            }
          });
        }
      });
    } else {
      userData = JSON.parse(localStorage.getItem('userDraftLocalData'));
      if (userData) {
        index = userData.map(function (el) { return el.socketId; }).indexOf(socket.id);
      }
      if (index != -1) {
        Request.post({
          "headers": { "content-type": "application/json" },
          "url": base_url + "api/snakeDrafts/changeUserStatus",
          "body": JSON.stringify({
            "ContestGUID": userData[index].ContestGUID,
            "UserGUID": userData[index].UserGUID,
            "DraftUserStatus": 'Offline'
          })
        }, (error, response, body) => {
          if (error) {
            //return console.dir(error);
          }
          //console.dir(body);

          responseData = JSON.parse(body);
          if (responseData.ResponseCode == 200) {

            Request.post({
              "headers": { "content-type": "application/json" },
              "url": base_url + "api/snakeDrafts/getRounds",
              "body": JSON.stringify({
                "SeriesGUID": userData[index].SeriesGUID,
                "ContestGUID": userData[index].ContestGUID,
              })
            }, (error, response, body) => {
              if (error) {
                //return console.dir(error);
              }
              //console.dir(body);
              draftJoinedContestUser = JSON.parse(body);
              if (draftJoinedContestUser.ResponseCode == 200) {
                io.to('draft_' + userData[index].ContestGUID).emit('draftJoinedContestUser', { ContestGUID: userData[index].ContestGUID, draftJoinedContestUser: draftJoinedContestUser, UserGUID: userData[index].UserGUID, UserStatus: 'Offline' });
                //console.log(index, 'index')
                //socket.leave('auction_'+userData[index].ContestGUID);
                userData.splice(index, 1);
                localStorage.setItem('userDraftLocalData', JSON.stringify(userData));
              }
            });
          }
        });
      }
    }

  });


  //Drat Code

  socket.on('DraftName', function (data) {
    console.log(JSON.stringify(data) + '---+++++++++++++++======');
    var userLocal = data;
    userLocal.socketId = socket.id
    userDraftLocalData.push(userLocal);
    localStorage.setItem('userDraftLocalData', JSON.stringify(userDraftLocalData));
    socket.leave('draft_' + data.ContestGUID);
    socket.join('draft_' + data.ContestGUID);
    console.log(base_url + "api/snakeDrafts/changeUserStatus")

    Request.post({
      "headers": { "content-type": "application/json" },
      "url": base_url + "api/snakeDrafts/changeUserStatus",
      "body": JSON.stringify({
        "ContestGUID": data.ContestGUID,
        "UserGUID": data.UserGUID,
        "DraftUserStatus": 'Online'
      })
    }, (error, response, body) => {
      if (error) {
        return console.dir(error);
      }
      var responseData = JSON.parse(body);
      if (responseData.ResponseCode == 200) {
        Request.post({
          "headers": { "content-type": "application/json" },
          "url": base_url + "api/snakeDrafts/getRounds",
          "body": JSON.stringify({
            "SeriesGUID": data.SeriesGUID,
            "ContestGUID": data.ContestGUID
          })
        }, (error, response, body) => {
          if (error) {
            //return console.dir(error);
          }
          //console.dir(body);
          var draftJoinedContestUser = JSON.parse(body);
          if (draftJoinedContestUser.ResponseCode == 200) {
            io.to('draft_' + data.ContestGUID).emit('draftJoinedContestUser', { ContestGUID: data.ContestGUID, draftJoinedContestUser: draftJoinedContestUser, UserGUID: data.UserGUID, UserStatus: 'Online' });
          }
        });
      }
    });

  });


  if (!intervalUserTime) {
    intervalUserTime = setInterval(function () {
      Request.post({
        "headers": { "content-type": "application/json" },
        "url": base_url + "api/snakeDrafts/getUserInLive",
        "body": ''
      }, (error, response, body) => {

        if (error) {
          //return console.dir(error);
        }
        var playerBidTimeData = JSON.parse(body);
        console.log('======== User in  Live dff')
        console.log(playerBidTimeData);

        if (playerBidTimeData.ResponseCode == 200 && playerBidTimeData.Data.length > 0) {
          for (let i = 0; i < playerBidTimeData.Data.length; i++) {

            let ContestGUID = playerBidTimeData.Data[i].ContestGUID;

            if (playerBidTimeData.Data[i].UserLiveInTimeSeconds >= (playerBidTimeData.Data[i].DraftUserTimer - 1) && playerBidTimeData.Data[i].UserStatus == 'Live') {
              console.log('yes==========================+++++++++++++++++----------------')
              var UserStatus = '';
              UserStatus = 'No';
              Request.post({
                "headers": { "content-type": "application/json" },
                "url": base_url + "api/snakeDrafts/draftPlayerSold",
                "body": JSON.stringify({
                  "SeriesGUID": playerBidTimeData.Data[i].SeriesGUID,
                  "ContestGUID": playerBidTimeData.Data[i].ContestGUID,
                  "TeamGUID": '',
                  "UserGUID": playerBidTimeData.Data[i].UserGUID,
                  "PlayerStatus": 'Sold'
                })
              }, (error, response, body) => {
                if (error) {
                  //return console.dir(error);
                }
                var responseData = JSON.parse(body);
                console.log(responseData);
                console.log('===========++++++++++responseData');
                if (responseData.ResponseCode == 200) {
                  if (responseData.Data.Status == 0) {
                    io.to('draft_' + responseData.Data.ContestGUID).emit('DraftBidError', { ContestGUID: responseData.Data.ContestGUID, responseData: responseData, UserGUID: playerBidTimeData.Data[i].UserGUID, Message: responseData.Message });

                  } else {
                    io.to('draft_' + playerBidTimeData.Data[i].ContestGUID).emit('DraftBidSuccess', { ContestGUID: playerBidTimeData.Data[i].ContestGUID, responseData: responseData });

                    Request.post({
                      "headers": { "content-type": "application/json" },
                      "url": base_url + "api/snakeDrafts/getRoundNextUserInLive",
                      "body": JSON.stringify({
                        "ContestGUID": playerBidTimeData.Data[i].ContestGUID,
                        "SeriesGUID": playerBidTimeData.Data[i].SeriesGUID
                      })
                    }, (error, response, body) => {
                      if (error) {
                        //return console.dir(error);
                      }

                      console.dir(body);
                      console.dir('body110000');
                      var playerBidData = JSON.parse(body);
                      console.log(playerBidData);
                      console.log('==========================playerBidData');
                      playerBidTimeData.Data[i].playerBidData = playerBidData;

                      //console.log(playerBidData);
                      if (playerBidTimeData.Data[i].playerBidData.ResponseCode == 200) {
                        Request.post({
                          "headers": { "content-type": "application/json" },
                          "url": base_url + "api/snakeDrafts/userLiveStatusUpdate",
                          "body": JSON.stringify({
                            "SeriesGUID": playerBidTimeData.Data[i].SeriesGUID,
                            "ContestGUID": playerBidTimeData.Data[i].ContestGUID,
                            "UserStatus": 'Yes',
                            "UserGUID": playerBidData.Data.UserGUID
                          })
                        }, (error, response, body) => {
                          if (error) {
                            //return console.dir(error);
                          }
                          console.dir(body);
                          console.dir('body1');
                          //console.dir(auctionPlayerStausData.Data.AuctionStatus);

                          var auctionPlayerStausData = JSON.parse(body);
                          io.in('draft_' + playerBidTimeData.Data[i].ContestGUID).emit('DraftUserChange', { ContestGUID: playerBidTimeData.Data[i].ContestGUID, UserGUID: playerBidTimeData.Data[i].playerBidData.Data.UserGUID, getBidPlayerData: playerBidTimeData.Data[i].playerBidData, PlayerStatus: auctionPlayerStausData, Datetime: auctionPlayerStausData.Data.DraftUserLiveTime, DraftUserTimer: playerBidData.Data.DraftUserTimer });

                          Request.post({
                            "headers": { "content-type": "application/json" },
                            "url": base_url + "api/snakeDrafts/getPlayersDraft",
                            "body": JSON.stringify({
                              "SeriesGUID": playerBidTimeData.Data[i].SeriesGUID,
                              "ContestGUID": playerBidTimeData.Data[i].ContestGUID,
                              "GameType": playerBidTimeData.Data[i].GameType,
                              "Params": "PlayerRoleShort,PlayerPosition,TeamName,BidSoldCredit,PlayerStatus,PlayerID,PlayerRole,PlayerPic,PlayerCountry,PlayerBattingStats,IsPlaying",
                              "PlayerBidStatus": "Yes",
                              "PlayerRoleShort": 'QB',
                              "PlayerStatus": 'Upcoming'
                              // "OrderBy" : 'FantasyPoints',
                              // "Sequence" : 'DESC'

                            })
                          }, (error, response, body) => {
                            if (error) {
                              //return console.dir(error);
                            }
                            //console.dir(body);
                            var auctionGetPlayer = JSON.parse(body);
                            io.in('draft_' + playerBidTimeData.Data[i].ContestGUID).emit('DraftPlayerStatus', { ContestGUID: playerBidTimeData.Data[i].ContestGUID, TeamGUID: playerBidTimeData.Data[i].playerBidData.Data.TeamGUID, getBidPlayerData: playerBidTimeData.Data[i].playerBidData, auctionGetPlayer: auctionGetPlayer, auctionPlayerStausData: auctionPlayerStausData, sta: 2, TimeDifference: playerBidTimeData.Data[i].TimeDifference });

                          });

                          Request.post({
                            "headers": { "content-type": "application/json" },
                            "url": base_url + "api/snakeDrafts/getRounds",
                            "body": JSON.stringify({
                              "SeriesGUID": playerBidTimeData.Data[i].SeriesGUID,
                              "ContestGUID": playerBidTimeData.Data[i].ContestGUID
                            })
                          }, (error, response, body) => {
                            if (error) {
                              //return console.dir(error);
                            }
                            //console.dir(body);
                            var draftJoinedContestUser = JSON.parse(body);
                            io.in('draft_' + playerBidTimeData.Data[i].ContestGUID).emit('draftJoinedContestUser', { ContestGUID: playerBidTimeData.Data[i].ContestGUID, TeamGUID: playerBidTimeData.Data[i].playerBidData.Data.TeamGUID, draftJoinedContestUser: draftJoinedContestUser });
                          });
                        });
                      }
                    });
                  }
                }
              });
            } else {
              if (playerBidTimeData.Data[i].UserStatus == 'Upcoming') {
                Request.post({
                  "headers": { "content-type": "application/json" },
                  "url": base_url + "api/snakeDrafts/getRoundNextUserInLive",
                  "body": JSON.stringify({
                    "ContestGUID": playerBidTimeData.Data[i].ContestGUID,
                    "SeriesGUID": playerBidTimeData.Data[i].SeriesGUID
                  })
                }, (error, response, body) => {
                  if (error) {
                    //return console.dir(error);
                  }

                  var playerBidData = JSON.parse(body);
                  console.log(playerBidData);
                  console.log('================playerBidData');
                  playerBidTimeData.Data[i].playerBidData = playerBidData;
                  if (playerBidTimeData.Data[i].playerBidData.ResponseCode == 200) {
                    Request.post({
                      "headers": { "content-type": "application/json" },
                      "url": base_url + "api/snakeDrafts/userLiveStatusUpdate",
                      "body": JSON.stringify({
                        "SeriesGUID": playerBidTimeData.Data[i].SeriesGUID,
                        "ContestGUID": playerBidTimeData.Data[i].ContestGUID,
                        "UserStatus": 'Yes',
                        "UserGUID": playerBidData.Data.UserGUID
                      })
                    }, (error, response, body) => {
                      if (error) {
                        //return console.dir(error);
                      }
                      //console.dir(body);
                      //console.dir('body222');
                      var auctionPlayerStausData = JSON.parse(body);
                      io.in('draft_' + playerBidTimeData.Data[i].ContestGUID).emit('DraftUserChange', { ContestGUID: playerBidTimeData.Data[i].ContestGUID, UserGUID: playerBidTimeData.Data[i].playerBidData.Data.UserGUID, getBidPlayerData: playerBidTimeData.Data[i].playerBidData, PlayerStatus: auctionPlayerStausData.Data.Status, Datetime: auctionPlayerStausData.Data.DraftUserLiveTime, DraftUserTimer: playerBidData.Data.DraftUserTimer });
                      //console.log(auctionPlayerStausData)
                      if (auctionPlayerStausData.ResponseCode == 200) {

                        Request.post({
                          "headers": { "content-type": "application/json" },
                          "url": base_url + "api/snakeDrafts/getPlayersDraft",
                          "body": JSON.stringify({
                            "SeriesGUID": playerBidTimeData.Data[i].SeriesGUID,
                            "ContestGUID": playerBidTimeData.Data[i].ContestGUID,
                            "Params": "PlayerRoleShort,PlayerPosition,TeamName,BidSoldCredit,PlayerStatus,PlayerID,PlayerRole,PlayerPic,PlayerCountry,PlayerBattingStats,IsPlaying",
                            "GameType": playerBidTimeData.Data[i].GameType,
                            "PlayerBidStatus": "Yes",
                            "PlayerRoleShort": 'QB',
                            "PlayerStatus": 'Upcoming'
                            // "OrderBy" : 'FantasyPoints',
                            // "Sequence" : 'DESC'     
                          })
                        }, (error, response, body) => {
                          if (error) {
                            //return console.dir(error);
                          }
                          //console.dir(body);
                          var auctionGetPlayer = JSON.parse(body);
                          if (auctionGetPlayer.ResponseCode == 200) {
                            if (typeof playerBidTimeData.Data[i].playerBidData == 'undefined') {
                              playerBidTimeData.Data[i].playerBidData = playerBidData;
                            }
                            console.log(playerBidTimeData.Data[i].playerBidData);
                            io.in('draft_' + playerBidTimeData.Data[i].ContestGUID).emit('DraftPlayerStatus', { ContestGUID: playerBidTimeData.Data[i].ContestGUID, TeamGUID: playerBidTimeData.Data[i].playerBidData.Data.TeamGUID, getBidPlayerData: playerBidTimeData.Data[i].playerBidData, auctionGetPlayer: auctionGetPlayer, auctionPlayerStausData: auctionPlayerStausData, sta: 3, TimeDifference: playerBidTimeData.Data[i].TimeDifference, PlayerStatus: auctionPlayerStausData.Data.Status });
                          }
                        });

                        Request.post({
                          "headers": { "content-type": "application/json" },
                          "url": base_url + "api/snakeDrafts/getRounds",
                          "body": JSON.stringify({
                            "SeriesGUID": playerBidTimeData.Data[i].SeriesGUID,
                            "ContestGUID": playerBidTimeData.Data[i].ContestGUID,
                          })
                        }, (error, response, body) => {
                          if (error) {
                            //return console.dir(error);
                          }
                          //console.dir(body);
                          var draftJoinedContestUser = JSON.parse(body);
                          if (draftJoinedContestUser.ResponseCode == 200) {
                            io.in('draft_' + playerBidTimeData.Data[i].ContestGUID).emit('draftJoinedContestUser', { ContestGUID: playerBidTimeData.Data[i].ContestGUID, TeamGUID: playerBidTimeData.Data[i].playerBidData.Data.TeamGUID, draftJoinedContestUser: draftJoinedContestUser });
                          }
                        });
                      }
                    });
                  }
                });
              }
            }
          }
        }
      });
    }, 1000 * 5);
  }


  if (!getDraftLive) {
    getDraftLive = setInterval(function () {
      Request.post({
        "headers": { "content-type": "application/json" },
        "url": base_url + "api/snakeDrafts/getDraftGameInLive",
        "body": ''
      }, (error, response, body) => {
        if (error) {
          //return console.dir(error);
        }
        //
        var responseData = JSON.parse(body);
        console.log('responseData');
        console.log(responseData);
        if (responseData.ResponseCode == 200 && responseData.Data.Data.TotalRecords > 0 && responseData.Data.Data.Records.length > 0) {
          //console.log(responseData.Data.Data.Records)
          for (let i = 0; i < responseData.Data.Data.Records.length; i++) {
            //console.log(responseData.Data.Data.Records[i].ContestGUID);
            Request.post({
              "headers": { "content-type": "application/json" },
              "url": base_url + "api/snakeDrafts/getDraftGameStatusUpdate",
              "body": JSON.stringify({
                "ContestGUID": responseData.Data.Data.Records[i].ContestGUID,
                "Status": "Running"
              })
            }, (error, response, body) => {
              if (error) {
                //return console.dir(error);
              }

              var getPlayersStatus = JSON.parse(body);
              console.log(getPlayersStatus)
              if (getPlayersStatus.ResponseCode == 200) {
                Request.post({
                  "headers": { "content-type": "application/json" },
                  "url": base_url + "api/snakeDrafts/getRoundNextUserInLive",
                  "body": JSON.stringify({
                    "ContestGUID": responseData.Data.Data.Records[i].ContestGUID,
                    "SeriesGUID": responseData.Data.Data.Records[i].SeriesGUID
                  })
                }, (error, response, body) => {
                  if (error) {
                    //return console.dir(error);
                  }
                  //console.dir(body);
                  var playerBidData = JSON.parse(body);
                  console.log(playerBidData)
                  if (playerBidData.ResponseCode == 200) {
                    Request.post({
                      "headers": { "content-type": "application/json" },
                      "url": base_url + "api/snakeDrafts/userLiveStatusUpdate",
                      "body": JSON.stringify({
                        "SeriesGUID": responseData.Data.Data.Records[i].SeriesGUID,
                        "UserStatus": 'Yes',
                        "ContestGUID": responseData.Data.Data.Records[i].ContestGUID,
                        "UserGUID": playerBidData.Data.UserGUID
                      })
                    }, (error, response, body) => {
                      if (error) {
                        //return console.dir(error);
                      }
                      var auctionPlayerStausData = JSON.parse(body);
                      io.in('draft_' + responseData.Data.Data.Records[i].ContestGUID).emit('DraftUserChange', { ContestGUID: responseData.Data.Data.Records[i].ContestGUID, UserGUID: playerBidData.Data.UserGUID, getBidPlayerData: playerBidData, PlayerStatus: auctionPlayerStausData.Data.Status, Datetime: auctionPlayerStausData.Data.DraftUserLiveTime, DraftUserTimer: playerBidData.Data.DraftUserTimer });

                      if (auctionPlayerStausData.ResponseCode == 200) {

                        Request.post({
                          "headers": { "content-type": "application/json" },
                          "url": base_url + "api/snakeDrafts/getPlayersDraft",
                          "body": JSON.stringify({
                            "SeriesGUID": responseData.Data.Data.Records[i].SeriesGUID,
                            "ContestGUID": responseData.Data.Data.Records[i].ContestGUID,
                            "GameType": responseData.Data.Data.Records[i].GameType,
                            "Params": "PlayerRoleShort,PlayerPosition,TeamName,BidSoldCredit,PlayerStatus,PlayerID,PlayerRole,PlayerPic,PlayerCountry,PlayerBattingStats,IsPlaying",
                            "PlayerBidStatus": "Yes",
                            "PlayerRoleShort": 'QB',
                            "PlayerStatus": 'Upcoming'
                            // "OrderBy" : 'FantasyPoints',
                            // "Sequence" : 'DESC'      
                          })
                        }, (error, response, body) => {
                          if (error) {
                            //return console.dir(error);
                          }
                          var auctionGetPlayer = JSON.parse(body);
                          if (auctionPlayerStausData.ResponseCode == 200) {
                            io.in('draft_' + responseData.Data.Data.Records[i].ContestGUID).emit('DraftPlayerStatus', { ContestGUID: responseData.Data.Data.Records[i].ContestGUID, UserGUID: playerBidData.Data.UserGUID, getBidPlayerData: playerBidData, auctionGetPlayer: auctionGetPlayer, sta: 1, auctionPlayerStausData: auctionPlayerStausData, TimeDifference: 30, PlayerStatus: auctionPlayerStausData.Data.Status });
                          }
                        });

                        Request.post({
                          "headers": { "content-type": "application/json" },
                          "url": base_url + "api/snakeDrafts/getRounds",
                          "body": JSON.stringify({
                            "SeriesGUID": responseData.Data.Data.Records[i].SeriesGUID,
                            "ContestGUID": responseData.Data.Data.Records[i].ContestGUID
                          })
                        }, (error, response, body) => {
                          if (error) {
                            //return console.dir(error);
                          }
                          var draftJoinedContestUser = JSON.parse(body);
                          if (draftJoinedContestUser.ResponseCode == 200) {
                            io.in('draft_' + responseData.Data.Data.Records[i].ContestGUID).emit('draftJoinedContestUser', { ContestGUID: responseData.Data.Data.Records[i].ContestGUID, TeamGUID: playerBidData.Data.TeamGUID, draftJoinedContestUser: draftJoinedContestUser });
                          }
                        });
                      }
                    });
                  }
                });
              }
              //console.dir(body);
            });

            io.in('draft_' + responseData.Data.Data.Records[i].ContestGUID).emit('DraftStart', { ContestGUID: responseData.Data.Data.Records[i].ContestGUID });
          }
        }
      });
    }, 1000 * 5);
  }

  socket.on('DraftBid', function (data) {
    console.log(data);
    console.log('DraftBid=======');
    Request.post({
      "headers": { "content-type": "application/json" },
      "url": base_url + "api/snakeDrafts/draftPlayerSold",
      "body": JSON.stringify({
        "SeriesGUID": data.SeriesGUID,
        "ContestGUID": data.ContestGUID,
        "PlayerGUID": data.PlayerGUID,
        "UserGUID": data.UserGUID,
        "PlayerRole": data.PlayerRole,
        "PlayerStatus": 'Sold'
      })
    }, (error, response, body) => {
      if (error) {
        //return console.dir(error);
      }
      //console.dir(body);
      var responseData = JSON.parse(body);
      console.log(responseData);
      console.log('draftPlayerSold');
      if (responseData.ResponseCode == 200) {
        if (responseData.Data.Status == 0) {
          io.to('draft_' + responseData.Data.ContestGUID).emit('DraftBidError', { ContestGUID: responseData.Data.ContestGUID, responseData: responseData, UserGUID: data.UserGUID, Message: responseData.Message });

        } else {
          io.to('draft_' + responseData.Data.ContestGUID).emit('DraftBidSuccess', { ContestGUID: data.ContestGUID, responseData: responseData });

          Request.post({
            "headers": { "content-type": "application/json" },
            "url": base_url + "api/snakeDrafts/getRoundNextUserInLive",
            "body": JSON.stringify({
              "ContestGUID": responseData.Data.ContestGUID,
              "SeriesGUID": responseData.Data.SeriesGUID
            })
          }, (error, response, body) => {
            if (error) {
              //return console.dir(error);
            }
            //console.dir(body);
            var playerBidData = JSON.parse(body);
            console.log(playerBidData)
            if (playerBidData.ResponseCode == 200) {
              Request.post({
                "headers": { "content-type": "application/json" },
                "url": base_url + "api/snakeDrafts/userLiveStatusUpdate",
                "body": JSON.stringify({
                  "SeriesGUID": responseData.Data.SeriesGUID,
                  "UserStatus": 'Yes',
                  "ContestGUID": responseData.Data.ContestGUID,
                  "UserGUID": playerBidData.Data.UserGUID
                })
              }, (error, response, body) => {
                if (error) {
                  //return console.dir(error);
                }
                var auctionPlayerStausData = JSON.parse(body);
                io.in('draft_' + responseData.Data.ContestGUID).emit('DraftUserChange', { ContestGUID: responseData.Data.ContestGUID, UserGUID: playerBidData.Data.UserGUID, getBidPlayerData: playerBidData, PlayerStatus: auctionPlayerStausData.Data.Status, Datetime: auctionPlayerStausData.Data.DraftUserLiveTime, DraftUserTimer: playerBidData.Data.DraftUserTimer });

                if (auctionPlayerStausData.ResponseCode == 200) {

                  Request.post({
                    "headers": { "content-type": "application/json" },
                    "url": base_url + "api/snakeDrafts/getPlayersDraft",
                    "body": JSON.stringify({
                      "SeriesGUID": responseData.Data.SeriesGUID,
                      "ContestGUID": responseData.Data.ContestGUID,
                      "GameType": playerBidData.Data.GameType,
                      "Params": "PlayerRoleShort,PlayerPosition,TeamName,BidSoldCredit,PlayerStatus,PlayerID,PlayerRole,PlayerPic,PlayerCountry,PlayerBattingStats,IsPlaying",
                      "PlayerBidStatus": "Yes",
                      "PlayerRoleShort": 'QB',
                      "PlayerStatus": 'Upcoming'
                      // "OrderBy" : 'FantasyPoints',
                      // "Sequence" : 'DESC'      
                    })
                  }, (error, response, body) => {
                    if (error) {
                      //return console.dir(error);
                    }
                    var auctionGetPlayer = JSON.parse(body);
                    if (auctionPlayerStausData.ResponseCode == 200) {
                      io.in('draft_' + responseData.Data.ContestGUID).emit('DraftPlayerStatus', { ContestGUID: responseData.Data.ContestGUID, UserGUID: playerBidData.Data.UserGUID, getBidPlayerData: playerBidData, auctionGetPlayer: auctionGetPlayer, sta: 1, auctionPlayerStausData: auctionPlayerStausData, TimeDifference: 30, PlayerStatus: auctionPlayerStausData.Data.Status });
                    }
                  });

                  Request.post({
                    "headers": { "content-type": "application/json" },
                    "url": base_url + "api/snakeDrafts/getRounds",
                    "body": JSON.stringify({
                      "SeriesGUID": responseData.Data.SeriesGUID,
                      "ContestGUID": responseData.Data.ContestGUID
                    })
                  }, (error, response, body) => {
                    if (error) {
                      //return console.dir(error);
                    }
                    var draftJoinedContestUser = JSON.parse(body);
                    if (draftJoinedContestUser.ResponseCode == 200) {
                      io.in('draft_' + responseData.Data.ContestGUID).emit('draftJoinedContestUser', { ContestGUID: responseData.Data.ContestGUID, TeamGUID: playerBidData.Data.TeamGUID, draftJoinedContestUser: draftJoinedContestUser });
                    }
                  });
                }
              });
            }
          });
        }
      }
    });
  });

  // NBA Draft code

  socket.on('NBADraftName', function (data) {
    console.log(JSON.stringify(data) + '---+++++++++++++++======');
    var userLocal = data;
    userLocal.socketId = socket.id
    NBAuserDraftLocalData.push(userLocal);
    localStorage.setItem('NBAuserDraftLocalData', JSON.stringify(NBAuserDraftLocalData));
    socket.leave('NBAdraft_' + data.ContestGUID);
    socket.join('NBAdraft_' + data.ContestGUID);
    console.log(nba_base_url + "snakeDrafts/changeUserStatus")

    Request.post({
      "headers": { "content-type": "application/json" },
      "url": nba_base_url + "snakeDrafts/changeUserStatus",
      "body": JSON.stringify({
        "ContestGUID": data.ContestGUID,
        "UserGUID": data.UserGUID,
        "DraftUserStatus": 'Online'
      })
    }, (error, response, body) => {
      // if (error) {
      //   return console.dir(error);
      // }	
      var responseData = JSON.parse(body);
      console.log(responseData);
      if (responseData.ResponseCode == 200) {
        Request.post({
          "headers": { "content-type": "application/json" },
          "url": nba_base_url + "snakeDrafts/getRounds",
          "body": JSON.stringify({
            "SeriesGUID": data.SeriesGUID,
            "ContestGUID": data.ContestGUID,
            "MatchGUID": data.MatchGUID
          })
        }, (error, response, body) => {
          if (error) {
            //return console.dir(error);
          }
          //console.dir(body);
          var draftJoinedContestUser = JSON.parse(body);
          if (draftJoinedContestUser.ResponseCode == 200) {
            io.to('NBAdraft_' + data.ContestGUID).emit('NBAdraftJoinedContestUser', { ContestGUID: data.ContestGUID, draftJoinedContestUser: draftJoinedContestUser, UserGUID: data.UserGUID, UserStatus: 'Online' });
          }
        });
      }
    });

  });

  if (!NBAintervalUserTime) {
    NBAintervalUserTime = setInterval(function () {
      Request.post({
        "headers": { "content-type": "application/json" },
        "url": nba_base_url + "snakeDrafts/getUserInLive",
        "body": ''
      }, (error, response, body) => {

        if (error) {
          //return console.dir(error);
        }
        var playerBidTimeData = JSON.parse(body);
        /*console.log('NBA ======== User in  Live dff')
        console.log(playerBidTimeData);*/

        if (playerBidTimeData.ResponseCode == 200 && playerBidTimeData.Data.length > 0) {
          for (let i = 0; i < playerBidTimeData.Data.length; i++) {

            let ContestGUID = playerBidTimeData.Data[i].ContestGUID;

            if (playerBidTimeData.Data[i].UserLiveInTimeSeconds >= (playerBidTimeData.Data[i].DraftUserTimer - 1) && playerBidTimeData.Data[i].UserStatus == 'Live') {
              console.log('yes==========================+++++++++++++++++----------------')
              var UserStatus = '';
              UserStatus = 'No';
              Request.post({
                "headers": { "content-type": "application/json" },
                "url": nba_base_url + "snakeDrafts/draftPlayerSold",
                "body": JSON.stringify({
                  "SeriesGUID": playerBidTimeData.Data[i].SeriesGUID,
                  "ContestGUID": playerBidTimeData.Data[i].ContestGUID,
                  "MatchGUID": playerBidTimeData.Data[i].MatchGUID,
                  "PlayerGUID": '',
                  "UserGUID": playerBidTimeData.Data[i].UserGUID,
                  "PlayerStatus": 'Sold'
                })
              }, (error, response, body) => {
                if (error) {
                  //return console.dir(error);
                }
                var responseData = JSON.parse(body);
                console.log(body);
                console.log('NBA sold===========++++++++++responseData');
                if (responseData.ResponseCode == 200) {
                  if (responseData.Data.Status == 0) {
                    io.to('NBAdraft_' + responseData.Data.ContestGUID).emit('NBADraftBidError', { ContestGUID: responseData.Data.ContestGUID, responseData: responseData, UserGUID: playerBidTimeData.Data[i].UserGUID, Message: responseData.Message });

                  } else {
                    io.to('NBAdraft_' + playerBidTimeData.Data[i].ContestGUID).emit('NBADraftBidSuccess', { ContestGUID: playerBidTimeData.Data[i].ContestGUID, responseData: responseData });

                    Request.post({
                      "headers": { "content-type": "application/json" },
                      "url": nba_base_url + "snakeDrafts/getRoundNextUserInLive",
                      "body": JSON.stringify({
                        "ContestGUID": playerBidTimeData.Data[i].ContestGUID,
                        "SeriesGUID": playerBidTimeData.Data[i].SeriesGUID
                      })
                    }, (error, response, body) => {
                      if (error) {
                        //return console.dir(error);
                      }

                      //console.dir(body);
                      //console.dir('body110000');
                      var playerBidData = JSON.parse(body);
                      console.log(playerBidData);
                      console.log('NBA ==========================playerBidData');
                      playerBidTimeData.Data[i].playerBidData = playerBidData;

                      //console.log(playerBidData);
                      if (playerBidTimeData.Data[i].playerBidData.ResponseCode == 200) {
                        Request.post({
                          "headers": { "content-type": "application/json" },
                          "url": nba_base_url + "snakeDrafts/userLiveStatusUpdate",
                          "body": JSON.stringify({
                            "SeriesGUID": playerBidTimeData.Data[i].SeriesGUID,
                            "ContestGUID": playerBidTimeData.Data[i].ContestGUID,
                            "UserStatus": 'Yes',
                            "UserGUID": playerBidData.Data.UserGUID
                          })
                        }, (error, response, body) => {
                          if (error) {
                            //return console.dir(error);
                          }
                          //console.dir(body);
                          //console.dir('body1');
                          //console.dir(auctionPlayerStausData.Data.AuctionStatus);

                          var auctionPlayerStausData = JSON.parse(body);
                          io.in('NBAdraft_' + playerBidTimeData.Data[i].ContestGUID).emit('NBADraftUserChange', { ContestGUID: playerBidTimeData.Data[i].ContestGUID, UserGUID: playerBidTimeData.Data[i].playerBidData.Data.UserGUID, getBidPlayerData: playerBidTimeData.Data[i].playerBidData, PlayerStatus: auctionPlayerStausData, Datetime: auctionPlayerStausData.Data.DraftUserLiveTime, DraftUserTimer: playerBidData.Data.DraftUserTimer });
                          console.log(playerBidTimeData.Data[i],'*************')
                          Request.post({
                            "headers": { "content-type": "application/json" },
                            "url": nba_base_url + "snakeDrafts/getPlayersDraft",
                            "body": JSON.stringify({
                              "SeriesGUID": playerBidTimeData.Data[i].SeriesGUID,
                              "ContestGUID": playerBidTimeData.Data[i].ContestGUID,
                              "MatchGUID": playerBidTimeData.Data[i].MatchGUID,
                              "GameType": playerBidTimeData.Data[i].GameType,
                              "Params": "TeamNameShort,IsInjuries,PlayerRoleShort,PlayerPosition,TeamName,BidSoldCredit,PlayerStatus,PlayerID,PlayerRole,PlayerPic,PlayerCountry,PlayerBattingStats,IsPlaying",
                              "PlayerBidStatus": "Yes",
                              "PlayerRoleShort": 'PG',
                              "PlayerStatus": 'Upcoming'
                              // "OrderBy" : 'FantasyPoints',
                              // "Sequence" : 'DESC'

                            })
                          }, (error, response, body) => {
                            if (error) {
                              //return console.dir(error);
                            }
                            //console.dir(body);
                            var auctionGetPlayer = JSON.parse(body);
                            console.log("1")
                            io.in('NBAdraft_' + playerBidTimeData.Data[i].ContestGUID).emit('NBADraftPlayerStatus', { ContestGUID: playerBidTimeData.Data[i].ContestGUID, TeamGUID: playerBidTimeData.Data[i].playerBidData.Data.TeamGUID, getBidPlayerData: playerBidTimeData.Data[i].playerBidData, auctionGetPlayer: auctionGetPlayer, auctionPlayerStausData: auctionPlayerStausData, sta: 2, TimeDifference: playerBidTimeData.Data[i].TimeDifference });

                          });

                          Request.post({
                            "headers": { "content-type": "application/json" },
                            "url": nba_base_url + "snakeDrafts/getRounds",
                            "body": JSON.stringify({
                              "SeriesGUID": playerBidTimeData.Data[i].SeriesGUID,
                              "ContestGUID": playerBidTimeData.Data[i].ContestGUID
                            })
                          }, (error, response, body) => {
                            if (error) {
                              //return console.dir(error);
                            }
                            //console.dir(body);
                            var draftJoinedContestUser = JSON.parse(body);
                            io.in('NBAdraft_' + playerBidTimeData.Data[i].ContestGUID).emit('NBAdraftJoinedContestUser', { ContestGUID: playerBidTimeData.Data[i].ContestGUID, TeamGUID: playerBidTimeData.Data[i].playerBidData.Data.TeamGUID, draftJoinedContestUser: draftJoinedContestUser });
                          });
                        });
                      }
                    });
                  }
                }
              });
            } else {
              if (playerBidTimeData.Data[i].UserStatus == 'Upcoming') {
                Request.post({
                  "headers": { "content-type": "application/json" },
                  "url": nba_base_url + "snakeDrafts/getRoundNextUserInLive",
                  "body": JSON.stringify({
                    "ContestGUID": playerBidTimeData.Data[i].ContestGUID,
                    "SeriesGUID": playerBidTimeData.Data[i].SeriesGUID
                  })
                }, (error, response, body) => {
                  if (error) {
                    //return console.dir(error);
                  }

                  var playerBidData = JSON.parse(body);
                  console.log(playerBidData);
                  console.log('================playerBidData');
                  playerBidTimeData.Data[i].playerBidData = playerBidData;
                  if (playerBidTimeData.Data[i].playerBidData.ResponseCode == 200) {
                    Request.post({
                      "headers": { "content-type": "application/json" },
                      "url": nba_base_url + "snakeDrafts/userLiveStatusUpdate",
                      "body": JSON.stringify({
                        "SeriesGUID": playerBidTimeData.Data[i].SeriesGUID,
                        "ContestGUID": playerBidTimeData.Data[i].ContestGUID,
                        "UserStatus": 'Yes',
                        "UserGUID": playerBidData.Data.UserGUID
                      })
                    }, (error, response, body) => {
                      if (error) {
                        //return console.dir(error);
                      }
                      //console.dir(body);
                      //console.dir('body222');
                      var auctionPlayerStausData = JSON.parse(body);
                      io.in('NBAdraft_' + playerBidTimeData.Data[i].ContestGUID).emit('NBADraftUserChange', { ContestGUID: playerBidTimeData.Data[i].ContestGUID, UserGUID: playerBidTimeData.Data[i].playerBidData.Data.UserGUID, getBidPlayerData: playerBidTimeData.Data[i].playerBidData, PlayerStatus: auctionPlayerStausData.Data.Status, Datetime: auctionPlayerStausData.Data.DraftUserLiveTime, DraftUserTimer: playerBidData.Data.DraftUserTimer });
                      //console.log(auctionPlayerStausData)
                      if (auctionPlayerStausData.ResponseCode == 200) {
                        console.log(playerBidTimeData.Data[i].MatchGUID,"-------------")
                        Request.post({
                          "headers": { "content-type": "application/json" },
                          "url": nba_base_url + "snakeDrafts/getPlayersDraft",
                          "body": JSON.stringify({
                            "SeriesGUID": playerBidTimeData.Data[i].SeriesGUID,
                            "ContestGUID": playerBidTimeData.Data[i].ContestGUID,
                            "MatchGUID": playerBidTimeData.Data[i].MatchGUID,
                            "Params": "TeamNameShort,IsInjuries,PlayerRoleShort,PlayerPosition,TeamName,BidSoldCredit,PlayerStatus,PlayerID,PlayerRole,PlayerPic,PlayerCountry,PlayerBattingStats,IsPlaying",
                            "GameType": playerBidTimeData.Data[i].GameType,
                            "PlayerBidStatus": "Yes",
                            "PlayerRoleShort": 'PG',
                            "PlayerStatus": 'Upcoming'
                            // "OrderBy" : 'FantasyPoints',
                            // "Sequence" : 'DESC'     
                          })
                        }, (error, response, body) => {
                          if (error) {
                            //return console.dir(error);
                          }
                          //console.dir(body);
                          var auctionGetPlayer = JSON.parse(body);
                          if (auctionGetPlayer.ResponseCode == 200) {
                            if (typeof playerBidTimeData.Data[i].playerBidData == 'undefined') {
                              playerBidTimeData.Data[i].playerBidData = playerBidData;
                            }
                            console.log(playerBidTimeData.Data[i].playerBidData);
                            console.log("2")
                            io.in('NBAdraft_' + playerBidTimeData.Data[i].ContestGUID).emit('NBADraftPlayerStatus', { ContestGUID: playerBidTimeData.Data[i].ContestGUID, TeamGUID: playerBidTimeData.Data[i].playerBidData.Data.TeamGUID, getBidPlayerData: playerBidTimeData.Data[i].playerBidData, auctionGetPlayer: auctionGetPlayer, auctionPlayerStausData: auctionPlayerStausData, sta: 3, TimeDifference: playerBidTimeData.Data[i].TimeDifference, PlayerStatus: auctionPlayerStausData.Data.Status });
                          }
                        });

                        Request.post({
                          "headers": { "content-type": "application/json" },
                          "url": nba_base_url + "snakeDrafts/getRounds",
                          "body": JSON.stringify({
                            "SeriesGUID": playerBidTimeData.Data[i].SeriesGUID,
                            "ContestGUID": playerBidTimeData.Data[i].ContestGUID,
                          })
                        }, (error, response, body) => {
                          if (error) {
                            //return console.dir(error);
                          }
                          //console.dir(body);
                          var draftJoinedContestUser = JSON.parse(body);
                          if (draftJoinedContestUser.ResponseCode == 200) {
                            io.in('NBAdraft_' + playerBidTimeData.Data[i].ContestGUID).emit('NBAdraftJoinedContestUser', { ContestGUID: playerBidTimeData.Data[i].ContestGUID, TeamGUID: playerBidTimeData.Data[i].playerBidData.Data.TeamGUID, draftJoinedContestUser: draftJoinedContestUser });
                          }
                        });
                      }
                    });
                  }
                });
              }
            }
          }
        }
      });
    }, 1000 * 5);
  }


  if (!NBAgetDraftLive) {
    NBAgetDraftLive = setInterval(function () {
      Request.post({
        "headers": { "content-type": "application/json" },
        "url": nba_base_url + "snakeDrafts/getDraftGameInLive",
        "body": ''
      }, (error, response, body) => {
        if (error) {
          //return console.dir(error);
        }
        //
        var responseData = JSON.parse(body);
        console.log('getDraftGameInLive ++++++++++++');
        console.log(responseData);
        if (responseData.ResponseCode == 200 && responseData.Data.Data.TotalRecords > 0 && responseData.Data.Data.Records.length > 0) {
          //console.log(responseData.Data.Data.Records)
          for (let i = 0; i < responseData.Data.Data.Records.length; i++) {
            // console.log(responseData.Data.Data.Records[i].ContestGUID);
            Request.post({
              "headers": { "content-type": "application/json" },
              "url": nba_base_url + "snakeDrafts/getDraftGameStatusUpdate",
              "body": JSON.stringify({
                "ContestGUID": responseData.Data.Data.Records[i].ContestGUID,
                "Status": "Running"
              })
            }, (error, response, body) => {
              if (error) {
                //return console.dir(error);
              }

              var getPlayersStatus = JSON.parse(body);
              console.log(getPlayersStatus, "getDraftGameStatusUpdate ++++++++")
              if (getPlayersStatus.ResponseCode == 200) {
                Request.post({
                  "headers": { "content-type": "application/json" },
                  "url": nba_base_url + "snakeDrafts/getRoundNextUserInLive",
                  "body": JSON.stringify({
                    "ContestGUID": responseData.Data.Data.Records[i].ContestGUID,
                    "SeriesGUID": responseData.Data.Data.Records[i].SeriesGUID
                  })
                }, (error, response, body) => {
                  if (error) {
                    //return console.dir(error);
                  }
                  //console.dir(body);
                  var playerBidData = JSON.parse(body);
                  console.log(playerBidData, "getRoundNextUserInLive+++++++++++++")
                  if (playerBidData.ResponseCode == 200) {
                    Request.post({
                      "headers": { "content-type": "application/json" },
                      "url": nba_base_url + "snakeDrafts/userLiveStatusUpdate",
                      "body": JSON.stringify({
                        "SeriesGUID": responseData.Data.Data.Records[i].SeriesGUID,
                        "UserStatus": 'Yes',
                        "ContestGUID": responseData.Data.Data.Records[i].ContestGUID,
                        "UserGUID": playerBidData.Data.UserGUID
                      })
                    }, (error, response, body) => {
                      if (error) {
                        //return console.dir(error);
                      }
                      var auctionPlayerStausData = JSON.parse(body);
                      io.in('NBAdraft_' + responseData.Data.Data.Records[i].ContestGUID).emit('NBADraftUserChange', { ContestGUID: responseData.Data.Data.Records[i].ContestGUID, UserGUID: playerBidData.Data.UserGUID, getBidPlayerData: playerBidData, PlayerStatus: auctionPlayerStausData.Data.Status, Datetime: auctionPlayerStausData.Data.DraftUserLiveTime, DraftUserTimer: playerBidData.Data.DraftUserTimer });

                      if (auctionPlayerStausData.ResponseCode == 200) {
                        console.log(responseData.Data.Data.Records[i].MatchGUID,"1231321213212")
                        Request.post({
                          "headers": { "content-type": "application/json" },
                          "url": nba_base_url + "snakeDrafts/getPlayersDraft",
                          "body": JSON.stringify({
                            "SeriesGUID": responseData.Data.Data.Records[i].SeriesGUID,
                            "ContestGUID": responseData.Data.Data.Records[i].ContestGUID,
                            "MatchGUID": responseData.Data.Data.Records[i].MatchGUID,
                            "GameType": responseData.Data.Data.Records[i].GameType,
                            "Params": "TeamNameShort,IsInjuries,PlayerRoleShort,PlayerPosition,TeamName,BidSoldCredit,PlayerStatus,PlayerID,PlayerRole,PlayerPic,PlayerCountry,PlayerBattingStats,IsPlaying",
                            "PlayerBidStatus": "Yes",
                            "PlayerRoleShort": 'PG',
                            "PlayerStatus": 'Upcoming'
                            // "OrderBy" : 'FantasyPoints',
                            // "Sequence" : 'DESC'      
                          })
                        }, (error, response, body) => {
                          if (error) {
                            //return console.dir(error);
                          }
                          var auctionGetPlayer = JSON.parse(body);
                          if (auctionPlayerStausData.ResponseCode == 200) {
                            console.log("3")
                            io.in('NBAdraft_' + responseData.Data.Data.Records[i].ContestGUID).emit('NBADraftPlayerStatus', { ContestGUID: responseData.Data.Data.Records[i].ContestGUID, UserGUID: playerBidData.Data.UserGUID, getBidPlayerData: playerBidData, auctionGetPlayer: auctionGetPlayer, sta: 1, auctionPlayerStausData: auctionPlayerStausData, TimeDifference: 30, PlayerStatus: auctionPlayerStausData.Data.Status });
                          }
                        });

                        Request.post({
                          "headers": { "content-type": "application/json" },
                          "url": nba_base_url + "snakeDrafts/getRounds",
                          "body": JSON.stringify({
                            "SeriesGUID": responseData.Data.Data.Records[i].SeriesGUID,
                            "ContestGUID": responseData.Data.Data.Records[i].ContestGUID
                          })
                        }, (error, response, body) => {
                          if (error) {
                            //return console.dir(error);
                          }
                          var draftJoinedContestUser = JSON.parse(body);
                          if (draftJoinedContestUser.ResponseCode == 200) {
                            io.in('NBAdraft_' + responseData.Data.Data.Records[i].ContestGUID).emit('NBAdraftJoinedContestUser', { ContestGUID: responseData.Data.Data.Records[i].ContestGUID, TeamGUID: playerBidData.Data.TeamGUID, draftJoinedContestUser: draftJoinedContestUser });
                          }
                        });
                      }
                    });
                  }
                });
              }
              //console.dir(body);
            });

            io.in('NBAdraft_' + responseData.Data.Data.Records[i].ContestGUID).emit('NBADraftStart', { ContestGUID: responseData.Data.Data.Records[i].ContestGUID });
          }
        }
      });
    }, 1000 * 5);
  }

  socket.on('NBADraftBid', function (data) {
    console.log(data);
    console.log('NBADraftBid=======');
    Request.post({
      "headers": { "content-type": "application/json" },
      "url": nba_base_url + "snakeDrafts/draftPlayerSold",
      "body": JSON.stringify({
        "SeriesGUID": data.SeriesGUID,
        "ContestGUID": data.ContestGUID,
        "MatchGUID": data.MatchGUID,
        "PlayerGUID": data.PlayerGUID,
        "UserGUID": data.UserGUID,
        "PlayerRole": data.PlayerRole,
        "PlayerStatus": 'Sold'
      })
    }, (error, response, body) => {
      if (error) {
        //return console.dir(error);
      }
      console.dir(body);
      var responseData = JSON.parse(body);
      console.log(responseData);
      console.log('draftPlayerSold');
      if (responseData.ResponseCode == 200) {
        if (responseData.Data.Status == 0) {
          io.to('NBAdraft_' + responseData.Data.ContestGUID).emit('NBADraftBidError', { ContestGUID: responseData.Data.ContestGUID, responseData: responseData, UserGUID: data.UserGUID, Message: responseData.Message });

        } else {
          io.to('NBAdraft_' + responseData.Data.ContestGUID).emit('NBADraftBidSuccess', { ContestGUID: data.ContestGUID, responseData: responseData });

          Request.post({
            "headers": { "content-type": "application/json" },
            "url": nba_base_url + "snakeDrafts/getRoundNextUserInLive",
            "body": JSON.stringify({
              "ContestGUID": responseData.Data.ContestGUID,
              "SeriesGUID": responseData.Data.SeriesGUID
            })
          }, (error, response, body) => {
            if (error) {
              //return console.dir(error);
            }
            //console.dir(body);
            var playerBidData = JSON.parse(body);
            console.log(playerBidData)
            if (playerBidData.ResponseCode == 200) {
              Request.post({
                "headers": { "content-type": "application/json" },
                "url": nba_base_url + "snakeDrafts/userLiveStatusUpdate",
                "body": JSON.stringify({
                  "SeriesGUID": responseData.Data.SeriesGUID,
                  "UserStatus": 'Yes',
                  "ContestGUID": responseData.Data.ContestGUID,
                  "UserGUID": playerBidData.Data.UserGUID
                })
              }, (error, response, body) => {
                if (error) {
                  //return console.dir(error);
                }
                var auctionPlayerStausData = JSON.parse(body);
                io.in('NBAdraft_' + responseData.Data.ContestGUID).emit('NBADraftUserChange', { ContestGUID: responseData.Data.ContestGUID, UserGUID: playerBidData.Data.UserGUID, getBidPlayerData: playerBidData, PlayerStatus: auctionPlayerStausData.Data.Status, Datetime: auctionPlayerStausData.Data.DraftUserLiveTime, DraftUserTimer: playerBidData.Data.DraftUserTimer });

                if (auctionPlayerStausData.ResponseCode == 200) {
                  console.log(responseData.Data.MatchGUID,"+++++++")
                  Request.post({
                    "headers": { "content-type": "application/json" },
                    "url": nba_base_url + "snakeDrafts/getPlayersDraft",
                    "body": JSON.stringify({
                      "SeriesGUID": responseData.Data.SeriesGUID,
                      "ContestGUID": responseData.Data.ContestGUID,
                      "MatchGUID": responseData.Data.MatchGUID,
                      "GameType": playerBidData.Data.GameType,
                      "Params": "TeamNameShort,IsInjuries,PlayerRoleShort,PlayerPosition,TeamName,BidSoldCredit,PlayerStatus,PlayerID,PlayerRole,PlayerPic,PlayerCountry,PlayerBattingStats,IsPlaying",
                      "PlayerBidStatus": "Yes",
                      "PlayerRoleShort": 'PG',
                      "PlayerStatus": 'Upcoming'
                      // "OrderBy" : 'FantasyPoints',
                      // "Sequence" : 'DESC'      
                    })
                  }, (error, response, body) => {
                    if (error) {
                      //return console.dir(error);
                    }
                    var auctionGetPlayer = JSON.parse(body);
                    if (auctionPlayerStausData.ResponseCode == 200) {
                      console.log("4")
                      io.in('NBAdraft_' + responseData.Data.ContestGUID).emit('NBADraftPlayerStatus', { ContestGUID: responseData.Data.ContestGUID, UserGUID: playerBidData.Data.UserGUID, getBidPlayerData: playerBidData, auctionGetPlayer: auctionGetPlayer, sta: 1, auctionPlayerStausData: auctionPlayerStausData, TimeDifference: 30, PlayerStatus: auctionPlayerStausData.Data.Status });
                    }
                  });

                  Request.post({
                    "headers": { "content-type": "application/json" },
                    "url": nba_base_url + "snakeDrafts/getRounds",
                    "body": JSON.stringify({
                      "SeriesGUID": responseData.Data.SeriesGUID,
                      "ContestGUID": responseData.Data.ContestGUID
                    })
                  }, (error, response, body) => {
                    if (error) {
                      //return console.dir(error);
                    }
                    var draftJoinedContestUser = JSON.parse(body);
                    if (draftJoinedContestUser.ResponseCode == 200) {
                      io.in('NBAdraft_' + responseData.Data.ContestGUID).emit('NBAdraftJoinedContestUser', { ContestGUID: responseData.Data.ContestGUID, TeamGUID: playerBidData.Data.TeamGUID, draftJoinedContestUser: draftJoinedContestUser });
                    }
                  });
                }
              });
            }
          });
        }
      }
    });
  });

});


var server = http.listen(port, function () {
  console.log('server is running ' + port);
});