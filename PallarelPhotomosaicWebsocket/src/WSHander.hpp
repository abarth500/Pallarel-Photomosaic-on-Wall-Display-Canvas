#ifndef WS_HANDLER_HPP
#define WS_HANDLER_HPP
#include "../../websocketpp/src/websocketpp.hpp"
#include "../../websocketpp/src/websocket_connection_handler.hpp"
#include "FindNearestNeighbor.hpp"

#include <boost/shared_ptr.hpp>

#include <string>
#include <vector>

using websocketpp::session_ptr;
using namespace std;
using namespace boost;

namespace PallarelPhotomosaicWebsocket {

class WSHandler : public websocketpp::connection_handler {
public:
	PallarelPhotomosaicWebsocket::FindNearestNeighbor fnn;

	WSHandler() {}
	virtual ~WSHandler() {}
	
	void setFNN(PallarelPhotomosaicWebsocket::FindNearestNeighbor&);

	// The echo server allows all domains is protocol free.
	void validate(session_ptr client);
	
	// an echo server is stateless. 
	// The handler has no need to keep track of connected clients.
	void on_fail(session_ptr client) {}
	void on_open(session_ptr client) {}
	void on_close(session_ptr client) {}
	
	// both text and binary messages are echoed back to the sending client.
	void on_message(session_ptr client,const std::string &msg);
	void on_message(session_ptr client,
		const std::vector<unsigned char> &data);
};

typedef boost::shared_ptr<WSHandler> WSHandler_ptr;

}

#endif // WS_HANDLER_HPP
