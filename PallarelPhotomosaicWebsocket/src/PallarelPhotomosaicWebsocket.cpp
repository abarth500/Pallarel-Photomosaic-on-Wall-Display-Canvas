#include "../../websocketpp/src/websocketpp.hpp"
#include "WSHander.hpp"
#include "FindNearestNeighbor.hpp"

#include <iostream>
#include <fstream>
#include <cstdlib>

#include <boost/asio.hpp>
#include <flann/flann.hpp>
#include <boost/random.hpp>
#include <boost/tokenizer.hpp>

using namespace std;
using namespace boost;

int main(int argc, char** argv) {
	PallarelPhotomosaicWebsocket::FindNearestNeighbor fnn;
	int nInput = 5000;
	int dim = 27;
	string host = "192.168.1.1";
	short port = 6166;
	if(argc>2){
		//本気モード
		nInput =atoi(argv[1]);
		host = argv[2];
		string inputfile = string(argv[3]);
		ifstream fin(inputfile);
		if(!fin){
			cerr << " not open." << endl;
			exit(0);
		}
		typedef boost::escaped_list_separator<char> Separator;
		typedef boost::tokenizer<Separator, std::string::iterator> Tokenizer;
		Separator separator;
		std::string readBuffer;
		int l = 0;
		int *tData = new int[(nInput * dim)];
		string *tUrls = new string[nInput];
		char colorhex[2];
		string hex;
		int color;
		int ln = 0;
		while(std::getline(fin, readBuffer)){
			if(ln % 1000 == 0){
				cout << endl << "[";
				cout.width(7);
				cout.fill(' ');
				cout << ln;
				cout << " - ";
				cout.width(7);
				cout.fill('0');
				cout << (ln + 1000);
				cout << "]\t";
			}
			if(ln % 50 == 0){
				cout << ".";
			}
			ln++;
			Tokenizer tokens(readBuffer.begin(), readBuffer.end(), separator);
			int f = 0;
			for (Tokenizer::iterator tok_iter = tokens.begin(); tok_iter != tokens.end(); ++tok_iter) {
				switch(f++){			
				case 0:
					//cout << "id:\t" << *tok_iter << endl;
					break;
				case 1:
					//cout << "hex:\t" << *tok_iter << endl;
					hex = *tok_iter;
					for (int i = 0; i < dim; i++) {
						colorhex[0] = hex.at(i*2);
						colorhex[1] = hex.at(i*2+1);
						color = strtoul(colorhex, NULL, 16);
						tData[l * dim + i] = color;
					}
					break;
				case 2:
					//cout << "url:\t" << *tok_iter << endl;
					tUrls[l] = *tok_iter;
					break;
				}
			}
			if(++l >= nInput){
				break;
			}
		}
		cout << "Done!" << endl << endl;
		fnn.input(tData,tUrls,nInput,dim);
		//fnn.findRandom(1,3);
	}else{
		//デモモード
		fnn.DEBUG = true;
		if(argc>1){
			nInput = int(argv[1]);
		}
		fnn.inputRandom(nInput,dim);
		fnn.findRandom(1,10);
	}

	std::string full_host;
	std::stringstream temp;	
	temp << host << ":" << port;
	full_host = temp.str();
	PallarelPhotomosaicWebsocket::WSHandler_ptr WSHandler(new PallarelPhotomosaicWebsocket::WSHandler());
	WSHandler->setFNN(fnn);
	try {
		boost::asio::io_service io_service;
		tcp::endpoint endpoint(tcp::v6(), port);
		
		websocketpp::server_ptr server(
			new websocketpp::server(io_service,endpoint,WSHandler)
		);
		
		server->add_host(host);
		server->add_host(full_host);
		
		// bump up max message size to maximum since we may be using the echo 
		// server to test performance and protocol extremes.
		server->set_max_message_size(websocketpp::frame::PAYLOAD_64BIT_LIMIT); 
		
		// start the server
		server->start_accept();
		
		std::cout << "Starting echo server on " << full_host << std::endl;
		
		// start asio
		io_service.run();

	} catch (std::exception& e) {
		std::cerr << "Exception: " << e.what() << std::endl;
	}
	return 0;
}
